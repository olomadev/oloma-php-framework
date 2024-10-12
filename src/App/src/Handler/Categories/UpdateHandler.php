<?php

declare(strict_types=1);

namespace App\Handler\Categories;

use App\Model\CategoryModel;
use App\Schema\Categories\CategorySave;
use App\Filter\Categories\SaveFilter;
use Olobase\Mezzio\DataManagerInterface;
use Olobase\Mezzio\Error\ErrorWrapperInterface as Error;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateHandler implements RequestHandlerInterface
{
    public function __construct(
        private CategoryModel $categoryModel,
        private DataManagerInterface $dataManager,
        private SaveFilter $filter,
        private Error $error,
    ) 
    {
        $this->categoryModel = $categoryModel;
        $this->dataManager = $dataManager;
        $this->error = $error;
        $this->filter = $filter;
    }
    
    /**
     * @OA\Put(
     *   path="/categories/update/{cateogoryId}",
     *   tags={"Categories"},
     *   summary="Update category",
     *   operationId="categories_update",
     *
     *   @OA\Parameter(
     *       name="categoryId",
     *       in="path",
     *       required=true,
     *       @OA\Schema(
     *           type="string",
     *       ),
     *   ),
     *   @OA\RequestBody(
     *     description="Update category",
     *     @OA\JsonContent(ref="#/components/schemas/CategorySave"),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad request, returns to validation errors"
     *   )
     *)
     **/
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $post = $request->getParsedBody();
        $this->filter->setInputData($post);
        $data = array();
        $response = array();
        if ($this->filter->isValid()) {
            $this->dataManager->setInputFilter($this->filter);
            $data = $this->dataManager->getSaveData(CategorySave::class, 'categories');
            $this->categoryModel->update($data, empty($post['move']) ? false : true);
        } else {
            return new JsonResponse($this->error->getMessages($this->filter), 400);
        }
        return new JsonResponse($response);   
    }
}
