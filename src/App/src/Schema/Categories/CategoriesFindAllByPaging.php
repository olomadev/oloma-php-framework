<?php

namespace App\Schema\Categories;

/**
 * @OA\Schema()
 */
class CategoriesFindAllByPaging
{
    /**
    *  @var array
    *  @OA\Property(
    *      type="array",
    *      @OA\Items(
    *           @OA\Property(
    *             property="categoryId",
    *             type="string",
    *           ),
    *           @OA\Property(
    *             property="parentId",
    *             type="string",
    *           ),
    *           @OA\Property(
    *             property="name",
    *             type="string",
    *           ),
    *           @OA\Property(
    *             property="slug",
    *             type="string",
    *           ),
    *           @OA\Property(
    *             property="rgt",
    *             type="number",
    *           ),
    *           @OA\Property(
    *             property="lft",
    *             type="number",
    *           ),
    *     ),
    *  )
    */
    public $data;
}
