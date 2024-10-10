<?php

declare(strict_types=1);

namespace App\Handler\Categories;

use App\Model\CategoryModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FindAllByPagingHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        return new FindAllByPagingHandler($container->get(CategoryModel::class));
    }
}
