<?php

namespace ReportingBundle\Utils\Model;

interface IndicatorInterface
{

    /**
     * get id
    */
    public function getId();

   /**
     * get code
    */
    public function setReference($reference);

    /**
     * get reference
    */
    public function getReference();

    /**
     * set code
    */
    public function setCode($code);

    /**
     * get code
    */
    public function getCode();

    /**
     * set filters
    */
    public function setFilters($filters = null);

    /**
     * get filters
    */
    public function getFilters();

    /**
     * set grpahique
    */
    public function setGraphique($graphique = null);

    /**
     * get graphique
    */
    public function getGraphique();


}