<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Provider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhoneBundle\PhoneBundle;

class ProvidersController extends Controller {

    /**
     * @Route("/providers")
     * @Method("GET")
     */
    public function getProviders() {

        $resp_code = 404;
        $providers = $this->getDoctrine()
            ->getRepository('AppBundle:Provider')
            ->findAll();

        if ($providers) {

            foreach ($providers as $provider) {
                $service_data = $this->getServicesProvided($provider->getId());

                $temp_data = array(
                    'id' => $provider->getId(),
                    'name' => $provider->getName(),
                    'location' => $provider->getLocation(),
                    'phone_number' => $provider->getPhoneNumber(),
                    'provides' => $service_data
                );

                $data[] = array_filter($temp_data);
            }

            $resp_code = 200;
        }

        return new Response(json_encode($data), $resp_code);
    }

    /**
     * @Route("/providers/{id}")
     * @Method("GET")
     */
    public function getProvider($id) {

        $data = array();
        $resp_code = 404;

        $provider = $this->getDoctrine()
            ->getRepository('AppBundle:Provider')
            ->find($id);

        if ($provider) {

            $service_data = $this->getServicesProvided($provider->getId());

            $temp_data = array(
                'id' => $provider->getId(),
                'name' => $provider->getName(),
                'location' => $provider->getLocation(),
                'phone_number' => $provider->getPhoneNumber(),
                'provides' => $service_data
            );
            $data = array_filter($temp_data);
            $resp_code = 200;
        }

        return new Response(json_encode($data), $resp_code);
    }

    /**
     * @Route("/providers")
     * @Method("POST")
     */
    public function createProvider(Request $request) {

        $resp_code = 422;
        $data = json_decode($request->getContent(), true);
        // retrieve data that passed in and set the variables
        // check for required fields name and location
        if(isset($data['name']) && isset($data['location'])) {

            $provider = new Provider();
            $provider->setName($data['name']);
            $provider->setLocation($data['location']);

            // check if optional phone_number is passed
            if(isset($data['phone_number'])) {
                // call helper function to format phone number so we can store in phone format (xxx-xxx-xxxx)
                $helper = new PhoneBundle();
                $provider->setPhoneNumber($helper->formatPhoneNumber($data['phone_number']));
            }

            $em = $this->getDoctrine()->getManager();
            // check if optional provided services is passed
            if(isset($data["provides"])) {
                $provides = $data['provides'];
                for($i=0; $i<sizeof($provides);$i++) {
                    // for each service, make sure it is valid by finding if it exists in the services table
                    $service = $em->getRepository('AppBundle:Service')->findOneByName($provides[$i]);
                    if($service) {
                        $provider->addService($em->getRepository('AppBundle:Service')->find($service->getId()));
                        $em->persist($provider);
                        $em->flush();
                    }
                }
            }
            else {
                // if no services provided, just insert record
                $em->persist($provider);
                $em->flush();
            }

            $resp_code = 201;
        }

        return new Response(json_encode($data), $resp_code);
    }

    /**
     * @Route("/providers/{id}")
     * @Method("PUT")
     */
    public function updateProvider($id, Request $request) {

        $resp_code = 422;
        $data = array();
        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository('AppBundle:Provider')->find($id);
        if ($provider) {

            $data = json_decode($request->getContent(), true);
            // FixMe: Better way to set variables?
            if(isset($data['name'])) $provider->setName($data['name']);
            if(isset($data['location'])) $provider->setLocation($data['location']);
            if(isset($data['phone_number'])) {
                // check if optional phone_number is passed and if it is null
                if(is_null($data['phone_number'])) {
                    $provider->setPhoneNumber(null);
                }
                else {
                    // call helper function to format phone number so we can store in phone format (xxx-xxx-xxxx)
                    $helper = new PhoneBundle();
                    $provider->setPhoneNumber($helper->formatPhoneNumber($data['phone_number']));
                }
            }

            if(isset($data['provides'])) {

                $provides = $data['provides'];

                // If provides is passed in the update it should overwrite the existing array.
                // Get services provided and then remove them before adding new ones
                $services = $this->getServicesProvided($id);
                if($services) {
                    for($i=0; $i<sizeof($services);$i++) {
                        $service_to_remove = $em->getRepository('AppBundle:Service')->findOneByName($services[$i]);
                        $provider->removeService($em->getRepository('AppBundle:Service')->find($service_to_remove->getId()));
                    }
                }
                // add services provided
                for($i=0; $i<sizeof($provides);$i++) {
                    $service_to_add = $em->getRepository('AppBundle:Service')->findOneByName($provides[$i]);
                    $provider->addService($em->getRepository('AppBundle:Service')->find($service_to_add->getId()));
                    $em->persist($provider);
                    $em->flush();
                }
            }

            $em->flush();
            $resp_code = 201;
        }

        return new Response(json_encode($data), $resp_code);
    }


    /**
     * @Route("/providers/{id}")
     * @Method("DELETE")
     */
    public function deleteProvider($id) {

        $resp_code = 404;
        $data = "Provider not found";

        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository('AppBundle:Provider')->find($id);

        if ($provider) {

            $services = $em->getRepository('AppBundle:Service')->find($provider->getId());
            if($services) {

                foreach ($services as $service_id) {
                    $provider->removeService($this->getEntityManager()->getReference('\Entity\Service', $service_id));
                }
            }

            $em->remove($provider);
            $em->flush();
            $data = "Provider deleted";
            $resp_code = 204;
        }

        return new Response($data, $resp_code);
    }

    /*
     * given a provider id, return list of services in array format
     */
    public function getServicesProvided($provider_id) {

        $em = $this->getDoctrine()->getManager();
        $services = $em->createQueryBuilder('c')
            ->select('s.name')
            ->from('AppBundle:Service', 's')
            ->innerJoin('s.provider', 'ps')
            ->where('ps.id = :provider_id')
            ->setParameter('provider_id', $provider_id)
            ->getQuery()
            ->getResult();

        $service_data = array();
        for($i=0; $i < sizeof($services); $i++) {
            $service_data[] = $services[$i]['name'];
        }
        return $service_data;
    }
}
?>