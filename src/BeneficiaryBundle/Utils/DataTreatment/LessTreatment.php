<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;

class LessTreatment extends AbstractTreatment
{

    /**
     * @param Project $project
     * @param array $householdsArray
     * @return array
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray)
    {
        foreach ($householdsArray as $householdArray)
        {
            foreach ($householdArray['data'] as $oldBeneficiaryArray)
            {
                $oldBeneficiary = $this->em->getRepository(Beneficiary::class)->find($oldBeneficiaryArray['id']);
                if (!$oldBeneficiary instanceof Beneficiary)
                    continue;
                $this->beneficiaryService->remove($oldBeneficiary);
            }
        }
        $listHouseholds = [];
        $this->addHouseholds($project);
        return $listHouseholds;
    }

    /**
     * @param Project $project
     * @throws \Exception
     */
    public function addHouseholds(Project $project)
    {
        $householdsToAdd = $this->getHouseholdOfStep1();
        foreach ($householdsToAdd as $householdToAdd)
        {
            try
            {
                $this->householdService->create($householdToAdd, $project);
            }
            catch (\Exception $exception)
            {
                continue;
            }
        }
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    private function getHouseholdOfStep1()
    {
        if (null === $this->token)
            return null;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);

        $fileContent = file_get_contents($dir_var . '/step_1');
        return json_decode($fileContent, true);
    }
}