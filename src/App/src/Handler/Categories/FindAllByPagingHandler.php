<?php

declare(strict_types=1);

namespace App\Handler\Categories;

use App\Model\CategoryModel;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FindAllByPagingHandler implements RequestHandlerInterface
{
    public function __construct(private CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @OA\Get(
     *   path="/categories/findAllByPaging",
     *   tags={"Categories"},
     *   summary="Find all categories tree",
     *   operationId="categories_findAllByPaging",
     *   
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(ref="#/components/schemas/CategoriesFindAllByPaging"),
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="No result found"
     *   )
     *)
     **/
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->categoryModel->findAllByPaging();
        return new JsonResponse([
            'data' => $data,
        ]);
    }

}
