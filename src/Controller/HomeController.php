<?php

declare(strict_types=1);

/**
 * This file is part of MyProject project.
 * (c) Caroline Noyer <studio@carolinenoyer.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(#[Autowire(env: 'PROJECT_NAME')] string $projectName): Response
    {
        return $this->render('home/index.html.twig', [
            'project_name' => $projectName,
        ]);
    }
}
