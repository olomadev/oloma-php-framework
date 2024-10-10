<?php

declare(strict_types=1);

namespace App\Handler\Categories;

use App\Model\CategoryModel;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FindAllHandler implements RequestHandlerInterface
{
    public function __construct(private CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @OA\Get(
     *   path="/categories/findAll",
     *   tags={"Categories"},
     *   summary="Find all categories",
     *   operationId="categories_findAll",
     *   
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(ref="#/components/schemas/CommonFindAll"),
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="No result found"
     *   )
     *)
     **/
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->categoryModel->findAll();
        return new JsonResponse([
            'data' => $data,
        ]);
    }

}
