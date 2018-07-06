<?php

namespace ReportingBundle\Utils\DataFillers\Country;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\DateTime;

use ReportingBundle\Utils\Model\IndicatorInterface;
use ReportingBundle\Utils\DataFillers\DataFillers;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Entity\ReportingValue;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Location;
use ReportingBundle\Entity\ReportingCountry;
use BeneficiaryBundle\Entity\ProjectBeneficiary;
use ProjectBundle\Entity\Project;
use DistributionBundle\Entity\DistributionBeneficiary;


class DataFillersCountry extends DataFillers
{

    private $em;
    private $repository;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }

    /**
     * find the id of reference code
     */
    public function getReferenceId(string $code) {
        $this->repository = $this->em->getRepository(ReportingIndicator::class);
        $qb = $this->repository->createQueryBuilder('ri')
                               ->Where('ri.code = :code')
                                    ->setParameter('code', $code);
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Fill in ReportingValue and ReportingCountry with total households
     */
    public function BMS_Country_TH() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Household::class);
            $qb = $this->repository->createQueryBuilder('h')
                                   ->leftjoin('h.location', 'l')
                                   ->select('Distinct count(h) AS value', 'l.countryIso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_TH");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('household');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with total beneficiaries
     */
    public function BMS_Country_TB() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Beneficiary::class);
            $qb = $this->repository->createQueryBuilder('b')
                                   ->leftjoin('b.household', 'h')
                                   ->leftjoin('h.location', 'l')
                                   ->select('count(b) AS value', 'l.countryIso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_TB");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('beneficiary');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with active projects
     */
    public function BMS_Country_AP() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Project::class);
            $qb = $this->repository->createQueryBuilder('p')
                                   ->Where('p.endDate < CURRENT_DATE()')
                                   ->select('count(p) AS value', 'p.iso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_AP");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('active project');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingCountry with total funding
     */
    public function BMS_Country_TF() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Project::class);
            $qb = $this->repository->createQueryBuilder('p')
                                   ->select('SUM(p.value) AS value', 'p.iso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_TF");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('dollar');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with enrolled beneficiaries
     */
    public function BMS_Country_EB() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
            $qb = $this->repository->createQueryBuilder('db')
                                   ->leftjoin('db.projectBeneficiary', 'pb')
                                   ->leftjoin('pb.project', 'p')
                                   ->select('count(db.id) AS value', 'p.iso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_EB");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('enrolled beneficiary');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingCountry with total number of distributions
     */
    public function BMS_Country_TND() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
            $qb = $this->repository->createQueryBuilder('db')
                                   ->leftjoin('db.projectBeneficiary', 'pb')
                                   ->leftjoin('pb.project', 'p')
                                   ->select('count(db.distributionData) AS value', 'p.iso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Country_TND");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('distributions');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }



}