<?php


namespace BeneficiaryBundle\Controller;


use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\HouseholdCSVService;
use BeneficiaryBundle\Utils\HouseholdService;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BeneficiaryBundle\Entity\Household;

//Annotations
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class HouseholdController extends Controller
{
    /**
     * @Rest\Get("/households/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Household $household
     * @return Response
     */
    public function showAction(Household $household)
    {
        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/households/get/all", name="all_households")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All households",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Household::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function allAction(Request $request)
    {
        $filters = $request->request->all();
        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');
        $households = $householdService->getAll($filters['__country'], $filters);

        $json = $this->get('jms_serializer')
            ->serialize(
                $households,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Put("/households", name="add_household_projects")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="household",
     *     in="body",
     *     required=true,
     *     @Model(type=Household::class, groups={"FullHousehold"})
     * )
     * 
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Household created",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $requestArray = $request->request->all();
        $projectsArray = $requestArray['projects'];

        $householdArray = $requestArray['household'];
        $householdArray['__country'] = $requestArray['__country'];

        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');
        try
        {
            $household = $householdService->createOrEdit($householdArray, $projectsArray, null);
        }
        catch (ValidationException $exception)
        {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }



    /**
     * @Rest\Post("/households/{id}", name="edit_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="household",
     *     in="body",
     *     required=true,
     *     @Model(type=Household::class, groups={"FullHousehold"})
     * )
     * 
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Household created",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Request $request
     * @param Household $household
     * @return Response
     */
    public function editAction(Request $request, Household $household)
    {
        $requestArray = $request->request->all();
        $projectsArray = $requestArray['projects'];

        $householdArray = $requestArray['household'];
        $householdArray['__country'] = $requestArray['__country'];

        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');
        try
        {
            $household = $householdService->createOrEdit($householdArray, $projectsArray, $household);
        }
        catch (ValidationException $exception)
        {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/import/households/project/{id}", name="import_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return Household (old and new) if similarity founded",
     *      examples={
     *          "application/json": {{
     *              "old": @Model(type=Household::class),
     *              "new": @Model(type=Household::class)
     *          }}
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function importAction(Request $request, Project $project)
    {
        if (!$request->query->has('step'))
            return new Response('You must specify the current level.');
        $step = $request->query->get('step');
        if ($request->query->has('token'))
            $token = $request->query->get('token');
        else
            $token = null;

        $contentJson = $request->request->all();
        $countryIso3 = $contentJson['__country'];
        unset($contentJson['__country']);
        /** @var HouseholdCSVService $householdService */
        $householdService = $this->get('beneficiary.household_csv_service');

        if (1 === intval($step))
        {
            if (!$request->files->has('file'))
                return new Response("You must upload a file.", 500);
            try
            {
                $return = $householdService->saveCSV($countryIso3, $project, $request->files->get('file'), $step, $token);
            }
            catch (\Exception $e)
            {
                return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        else
        {
            try
            {
                $return = $householdService->foundErrors($countryIso3, $project, $contentJson, $step, $token);
            }
            catch (\Exception $e)
            {
                return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $json = $this->get('jms_serializer')
            ->serialize($return, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(["FullHousehold"]));
        return new Response($json);
    }

    /**
     * @Rest\Get("/csv/households/export", name="get_pattern_csv_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return Household (old and new) if similarity founded",
     *      examples={
     *          "application/json": {
     *              {
     *                  "'Household','','','','','','','','','','','Beneficiary','','','','','',''\n'Address street','Address number','Address postcode','Livelihood','Notes','Latitude','Longitude','Adm1','Adm2','Adm3','Adm4','Family name','Gender','Status','Date of birth','Vulnerability criteria','Phones','National IDs'\n",
     *                  "pattern_household_fra.csv"
     *              }
     *          }
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getPatternCSVAction(Request $request)
    {
        $countryIso3 = $request->request->get('__country');
        
        $type = $request->query->get('type') ?: 'csv';
        /** @var ExportCSVService $exportCSVService */
        $exportCSVService = $this->get('beneficiary.household_export_csv_service');
        try
        {
            $filename = $exportCSVService->generate($countryIso3, $type);

            // Create binary file to send
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
            if ($mimeTypeGuesser->isSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd() . '/' . $filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Post("/import/api/households/project/{id}", name="get_all_beneficiaries_via_api")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function importBeneficiariesFromAPIAction(Request $request, Project $project)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];
        $provider = strtolower($body['provider']);
        $params = $body['params'];

        try {
            $response = $this->get('beneficiary.api_import_service')->import($countryIso3, $provider, $params, $project);

            $json = $this->get('jms_serializer')
                ->serialize($response, 'json');

            return new Response($json);
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/import/api/households/list", name="get_all_api_available_for_country")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAPIAction(Request $request)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];

        $APINames = $this->get('beneficiary.api_import_service')->getAllAPI($countryIso3);

        return new Response(json_encode($APINames));
    }
}