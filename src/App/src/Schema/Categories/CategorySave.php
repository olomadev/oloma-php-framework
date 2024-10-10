<?php

namespace App\Schema\Categories;

/**
 * @OA\Schema()
 */
class CategorySave
{
    /**
     * @var string
     * @OA\Property(
     *     format="uuid"
     * )
     */
    public $categoryId;
    /**
     * @var string
     * @OA\Property(
     *     format="uuid"
     * )
     */
    public $parentId;
    /**
     * @var string
     * @OA\Property()
     */
    public $name;
    /**
     * @var integer
     * @OA\Property()
     */
    public $lft;
    /**
     * @var integer
     * @OA\Property()
     */
    public $rgt;
}
