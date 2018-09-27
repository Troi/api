<?php

namespace BeneficiaryBundle\Utils\ImportProvider\KHM;

use CommonBundle\Entity\Adm4;
use Doctrine\ORM\EntityManagerInterface;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Utils\ImportProvider\DefaultAPIProvider;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Utils\LocationService;
use DateTime;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Class KHMApiProvider
 * @package BeneficiaryBundle\Utils\ImportProvider
 */
class KHMIDPoorAPIProvider extends DefaultAPIProvider {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var ValidatorInterface $validator */
    private $validator;

    /**
     * @var string
     */
    private $url = "http://hub.cam-monitoring.info";

    /** @var LocationService $locationService */
    private $locationService;

    /**
     * KHMApiProvider constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container= $container;
        $this->locationService = $this->container->get('location_service');
    }

    /**
     * Import beneficiaries from API
     * @param string $countryIso3
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function importData(string $countryIso3, array $params)
    {
        if(!key_exists('countryCode', $params))
            throw new \Exception("Missing countryCode key in the array");

        $countryIso2 = "";
        $countryCode = "";

        for ($i = 0; $i < strlen($params['countryCode']); $i++){
            if($i < 2)
                $countryIso2 = $countryIso2 . $params['countryCode'][$i];

            else
                $countryCode = $countryCode . $params['countryCode'][$i];
        }

        $route = "/api/idpoor8/". $countryCode .".json?email=james.happell%40peopleinneed.cz&token=K45nDocxQ5sEFfqSWwDm-2DxskYEDYFe";
        
        try {
            $villages = $this->sendRequest("GET", $route);

            $beneficiariesArray = array();

            foreach ($villages as $village) {

                $this->saveAdm4($village, $countryIso2, $params['countryCode']);

                foreach ($village['HouseholdMembers'] as $householdMember) {
                    for($i = 0; $i < strlen($householdMember['MemberName']); $i++){
                        if($householdMember['MemberName'][$i] == ' ')
                            $bothName = explode(' ', $householdMember['MemberName']);
                    }

                    $givenName = $bothName[0];
                    $familyName = $bothName[1];

                    if($householdMember['RelationshipToHH'] == "Head of Household")
                        $headerHousehold = 1;
                    else
                        $headerHousehold = 0;

                    if($householdMember['Sex'] == 'Man')
                        $sex = 1;
                    else
                        $sex = 0;

                    array_push($beneficiariesArray, array(
                            'equityCardNo' => $householdMember['EquityCardNo'],
                            'status' => $headerHousehold,
                            'givenName' => $givenName,
                            'familyName' => $familyName,
                            'IDPoor' => $householdMember['PovertyLevel'],
                            'gender' => $sex,
                            'dateOfBirth' => $householdMember['YearOfBirth'] . '-01-01'
                        )
                    );
                }
            }

            //Sort by equityNumber
            asort($beneficiariesArray);

            return $this->parseData($beneficiariesArray, $countryIso3, $params['countryCode']);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Send request to WING API for Cambodia
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route) {
        $curl = curl_init();
        
        $headers = array();

        array_push($headers, "Authorization: Basic d2ZwOndmcCMxMjM0NQ==");
                
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => "8383",
          CURLOPT_URL            => $this->url . $route,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => $type,
          CURLOPT_HTTPHEADER     => $headers,
          CURLOPT_FAILONERROR    => true,
          CURLINFO_HEADER_OUT    => true
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        } else {
            $result = json_decode($response, true);
            return $result;
        }
    }

    /**
     * @param array $beneficiariesArray
     * @param string $countryIso3
     * @param string $countryIso2
     * @param string $countryCode
     * @param array $allCountryCode
     * @return array
     * @throws ValidationException
     * @throws \Exception
     */
    public function parseData(array $beneficiariesArray, string $countryIso3, string $allCountryCode){
        $oldEquityNumber = "";
        $beneficiariesInHousehold = array();

        foreach ($beneficiariesArray as $allBeneficiary){
            if($oldEquityNumber != $allBeneficiary['equityCardNo'] && $oldEquityNumber != ""){

                try {
                    $household = $this->createAndInitHousehold($beneficiariesInHousehold);
                } catch (\Exception $e) {
                    throw $e;
                }

                if($household != 'beneficiariesExist'){
                    try {
                        $location = $this->getLocation($this->locationService, $countryIso3, $allCountryCode);
                    } catch (ValidationException $e) {
                        throw $e;
                    }
                    $household->setLocation($location);

                    $this->em->persist($household);

                    try {
                        $this->insertBeneficiaries($household, $beneficiariesInHousehold);
                    } catch (\Exception $e) {
                        throw $e;
                    }

                    $countrySpecificAnswer = $this->setCountrySpecificAnswer($countryIso3, $household, $beneficiariesInHousehold);

                    $this->em->persist($countrySpecificAnswer);

                    $this->em->flush();
                    $this->setHousehold($household);

                    unset($beneficiariesInHousehold);
                    $beneficiariesInHousehold = array();
                }
            }

            $oldEquityNumber = $allBeneficiary['equityCardNo'];
            array_push($beneficiariesInHousehold, $allBeneficiary);
        }

        return ['message' => 'Insertion successfully'];
    }

    /**
     * @param array $beneficiariesInHousehold
     * @return Household|string
     * @throws \Exception
     */
    private function createAndInitHousehold(array $beneficiariesInHousehold){

        $dateOfBirth = new DateTime($beneficiariesInHousehold[0]['dateOfBirth']);
        $familyName = $beneficiariesInHousehold[0]['familyName'];
        $givenName = $beneficiariesInHousehold[0]["givenName"];
        $status = $beneficiariesInHousehold[0]['status'];
        $gender = $beneficiariesInHousehold[0]['gender'];

        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $givenName, 'familyName' => $familyName, 'gender' => $gender, 'status' => $status, 'dateOfBirth' => $dateOfBirth]);

        if($beneficiary)
            return "beneficiariesExist";

        /** @var Household $household */
        $household = new Household();
        $household->setNotes(null)
            ->setLivelihood(null)
            ->setLongitude(null)
            ->setLatitude(null)
            ->setAddressStreet(null)
            ->setAddressPostcode(null)
            ->setAddressNumber(null);

        $errors = $this->validator->validate($household);
        if (count($errors) > 0)
        {
            $errorsMessage = "";
            /** @var ConstraintViolation $error */
            foreach ($errors as $error)
            {
                if ("" !== $errorsMessage)
                    $errorsMessage .= " ";
                $errorsMessage .= $error->getMessage();
            }

            throw new \Exception($errorsMessage);
        }

        return $household;
    }

    /**
     * @param LocationService $locationService
     * @param string $countryISO3
     * @param string $allCountryCode
     * @return \CommonBundle\Entity\Location|null|object
     * @throws ValidationException
     * @throws \Exception
     */
    private function getLocation(LocationService $locationService, string $countryISO3, string $allCountryCode){


        $adm3 = $this->em->getRepository(Adm3::class)->findOneBy(['code' => $allCountryCode]);
        if($adm3 == null)
            throw new \Exception("Adm3 was not found.");

        $adm2 = $this->em->getRepository(Adm2::class)->find($adm3->getAdm2());
        $adm1 = $this->em->getRepository(Adm1::class)->find($adm2->getAdm1());

        $householdArray = array(
            'location' => array(
                'adm1' => $adm1->getName(),
                'adm2' => $adm2->getName(),
                'adm3' => $adm3->getName()
            )
        );

        $location = $locationService->getOrSaveLocation($countryISO3, $householdArray["location"]);

        if (null === $location)
            throw new \Exception("Location was not found.");

        return $location;
    }

    /**
     * @param Household $household
     * @param array $beneficiariesInHousehold
     * @throws \Exception
     */
    private function insertBeneficiaries(Household $household, array $beneficiariesInHousehold){

        if (!empty($beneficiariesInHousehold))
        {
            $hasHead = false;
            $beneficiariesPersisted = [];
            foreach ($beneficiariesInHousehold as $beneficiaryToSave)
            {
                try
                {
                    $beneficiary = $this->createBeneficiary($household, $beneficiaryToSave);
                    $beneficiariesPersisted[] = $beneficiary;
                }
                catch (\Exception $exception)
                {
                    throw new \Exception($exception->getMessage());
                }
                if ($beneficiary->getStatus())
                {
                    if ($hasHead)
                    {
                        throw new \Exception("You have defined more than 1 head of household.");
                    }
                    $hasHead = true;
                }
                $this->em->persist($beneficiary);
            }
        }
    }

    /**
     * @param string $countryISO3
     * @param Household $household
     * @param array $beneficiariesInHousehold
     * @return CountrySpecificAnswer
     */
    private function setCountrySpecificAnswer(string $countryISO3, Household $household, array $beneficiariesInHousehold){
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->findOneBy(['fieldString' => 'ID Poor', 'countryIso3' => $countryISO3]);

        $countrySpecificAnswer = new CountrySpecificAnswer();
        $countrySpecificAnswer->setCountrySpecific($countrySpecific)
            ->setHousehold($household)
            ->setAnswer($beneficiariesInHousehold[0]['IDPoor']);

        return $countrySpecificAnswer;
    }

    /**
     * @param Household $household
     */
    private function setHousehold(Household $household){
        $household = $this->em->getRepository(Household::class)->find($household->getId());
        $country_specific_answer = $this->em->getRepository(CountrySpecificAnswer::class)->findByHousehold($household);
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);

        $household->addCountrySpecificAnswer($country_specific_answer[0]);

        foreach ($beneficiaries as $beneficiary)
        {
            $phones = $this->em->getRepository(Phone::class)
                ->findByBeneficiary($beneficiary);
            $nationalIds = $this->em->getRepository(NationalId::class)
                ->findByBeneficiary($beneficiary);
            foreach ($phones as $phone)
            {
                $beneficiary->addPhone($phone);
            }
            foreach ($nationalIds as $nationalId)
            {
                $beneficiary->addNationalId($nationalId);
            }
            $household->addBeneficiary($beneficiary);
        }
    }

    /**
     * @param Household $household
     * @param array $beneficiaryArray
     * @return Beneficiary
     */
    private function createBeneficiary(Household $household, array $beneficiaryArray){

        $beneficiary = new Beneficiary();
        $beneficiary->setHousehold($household);

        $beneficiary->setGender($beneficiaryArray["gender"])
            ->setDateOfBirth(new \DateTime($beneficiaryArray["dateOfBirth"]))
            ->setFamilyName($beneficiaryArray["familyName"])
            ->setGivenName($beneficiaryArray["givenName"])
            ->setStatus($beneficiaryArray["status"]);

        $this->createProfile($beneficiary);

        $this->em->persist($beneficiary);

        return $beneficiary;
    }

    /**
     * @param Beneficiary $beneficiary
     * @return Profile
     */
    private function createProfile(Beneficiary $beneficiary){

        $profile = new Profile();

        /** @var Profile $profile */
        $profile->setPhoto("");
        $this->em->persist($profile);

        $beneficiary->setProfile($profile);
        $this->em->persist($beneficiary);

        return $profile;
    }

    /**
     * @param array $village
     * @param string $countryIso2
     * @param string $allCountryCode
     * @throws \Exception
     */
    private function saveAdm4(array $village, string $countryIso2, string $allCountryCode){

        $adm3 = $this->em->getRepository(Adm3::class)->findOneBy(['code' => $allCountryCode]);
        if($adm3 == null)
            throw new \Exception("Adm3 was not found.");

        $checkAdm4 = $this->em->getRepository(Adm4::class)->findOneBy(['name' => $village['VillageName'], 'adm3' => $adm3]);
        if(!$checkAdm4){
            $adm4 = new Adm4();




            $adm4->setName($village['VillageName'])
                ->setAdm3($adm3)
                ->setCode($countryIso2 . $village['VillageCode']);

            $this->em->persist($adm4);
            $this->em->flush();
        }
    }
}