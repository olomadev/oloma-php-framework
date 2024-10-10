<?php

declare(strict_types=1);

namespace App\Handler\Categories;

use App\Model\CategoryModel;
use App\Filter\Categories\DeleteFilter;
use Olobase\Mezzio\Error\ErrorWrapperInterface as Error;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteHandler implements RequestHandlerInterface
{
    public function __construct(
        private CategoryModel $categoryModel,        
        private DeleteFilter $filter,
        private Error $error,
    ) 
    {
        $this->categoryModel = $categoryModel;
        $this->filter = $filter;
        $this->error = $error;
    }
    
    /**
     * @OA\Delete(
     *   path="/categories/delete/{categoryId}",
     *   tags={"Categories"},
     *   summary="Delete category",
     *   operationId="category_delete",
     *
     *   @OA\Parameter(
     *       in="path",
     *       name="categoryId",
     *       required=true,
     *       description="Category uuid",
     *       @OA\Schema(
     *           type="string",
     *           format="uuid",
     *       ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *   )
     *)
     **/
    public function handle(ServerRequestInterface $request): ResponseInterface
    {   
        $this->filter->setInputData($request->getQueryParams());
        if ($this->filter->isValid()) {
            $this->categoryModel->delete(
                $this->filter->getValue('id')
            );
        } else {
            return new JsonResponse($this->error->getMessages($this->filter), 400);
        }
        return new JsonResponse([]);
    }
}
