<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Service
 *
 * @ORM\Table(name="service")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceRepository")
 */
class Service
{

    /*private $providers;
    public function addProvider(Provider $provider) {
        $this->providers[] = $provider;
    }*/

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;


    /**
     * @ORM\ManyToMany(targetEntity="Provider", inversedBy="service", cascade={"persist"})
     * @ORM\JoinTable(name="provide_service",
     * joinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")},
     * inverseJoinColumns={@ORM\JoinColumn(name="provider_id", referencedColumnName="id")}
     * )
     */
    private $provider;

    public function __construct()
    {
      #  $this->provider = \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add provider
     *
     * @param \AppBundle\Entity\Provider $provider
     */
    public function addProvider(\AppBundle\Entity\Provider $provider)
    {
        $this->provider[] = $provider;
    }



    /**
     * Get provider
     *
     *
     */
    public function getProvider()
    {
        return $this->provider->toArray();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Service
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
}
