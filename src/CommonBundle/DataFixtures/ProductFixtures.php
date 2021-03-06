<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use VoucherBundle\Entity\Product;

class ProductFixtures extends Fixture
{
    private $data = [
        ['soap', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a1505596a4.jpg', 0, 'SYR'],
        ['toothbrush', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15252e30e.jpg', 0, 'SYR'],
        ['pear', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a158059d28.jpg', 0, 'SYR'],
        ['rice', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a154759a4d.jpg', 0, 'SYR'],
        ['flour', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a159580fdf.jpg', 0, 'SYR'],
        ['toothpaste', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15a54ae7f.jpg', 0, 'SYR'],
        ['apple', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15d379307.jpeg', 0, 'SYR'],
        ['cherry', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a160605fe0.jpg', 0, 'SYR'],
        ['book', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a161a9d3dd.png', 0, 'SYR'],
        ['cake', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a162f9cdeb.jpg', 0, 'SYR'],

    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $product = new Product();
            $product->setName($datum[0])
                ->setUnit($datum[1])
                ->setImage($datum[2])
                ->setArchived($datum[3])
                ->setCountryISO3($datum[4]);
            $manager->persist($product);
            $manager->flush();
        }
    }
}
