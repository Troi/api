<?php

namespace CommonBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm1;
use Doctrine\ORM\Query\Expr\Join;

/**
 * LocationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Create sub request to get the adm1 of a location
     *
     * @param QueryBuilder $qb
     */
    public function getAdm1(QueryBuilder &$qb)
    {
        $qb->leftJoin("l.adm4", "adm4")
            ->leftJoin("l.adm3", "locAdm3")
            ->leftJoin("l.adm2", "locAdm2")
            ->leftJoin("l.adm1", "locAdm1")
            ->leftJoin(Adm3::class, "adm3", Join::WITH, "adm3.id = COALESCE(IDENTITY(adm4.adm3, 'id'), locAdm3.id)")
            ->leftJoin(Adm2::class, "adm2", Join::WITH, "adm2.id = COALESCE(IDENTITY(adm3.adm2, 'id'), locAdm2.id)")
            ->leftJoin(Adm1::class, "adm1", Join::WITH, "adm1.id = COALESCE(IDENTITY(adm2.adm1, 'id'), locAdm1.id)");
    }

    /**
     * Create sub request to get the adm2 of a location
     *
     * @param QueryBuilder $qb
     */
    public function getAdm2(QueryBuilder &$qb)
    {
        $qb->leftJoin("l.adm4", "adm4")
            ->leftJoin("l.adm3", "locAdm3")
            ->leftJoin("l.adm2", "locAdm2")
            ->leftJoin(Adm3::class, "adm3", Join::WITH, "adm3.id = COALESCE(IDENTITY(adm4.adm3, 'id'), locAdm3.id)")
            ->leftJoin(Adm2::class, "adm2", Join::WITH, "adm2.id = COALESCE(IDENTITY(adm3.adm2, 'id'), locAdm2.id)");
    }

     /**
     * Create sub request to get the adm3 of a location
     *
     * @param QueryBuilder $qb
     */
    public function getAdm3(QueryBuilder &$qb)
    {
        $qb->leftJoin("l.adm4", "adm4")
            ->leftJoin("l.adm3", "locAdm3")
            ->leftJoin(Adm3::class, "adm3", Join::WITH, "adm3.id = COALESCE(IDENTITY(adm4.adm3, 'id'), locAdm3.id)");
    }

    /**
     * Create sub request to get items in country.
     * The location must be in the country ($countryISO3).
     *
     * @param QueryBuilder $qb
     * @param $countryISO3
     */
    public function whereCountry(QueryBuilder &$qb, $countryISO3)
    {
        $this->getAdm1($qb);
        $qb->andWhere("adm1.countryISO3 = :iso3")
            ->setParameter("iso3", $countryISO3);
    }

    public function getCountry(QueryBuilder &$qb)
    {
        $this->getAdm1($qb);
        $qb->select("adm1.countryISO3 as country");
    }
}
