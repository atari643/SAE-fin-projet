<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MotherController extends AbstractController
{


    public function __construct()
    {
        define("App\Controller\ITEMS_PER_PAGE", 10);
    }
}
