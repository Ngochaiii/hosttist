<?php

namespace App\Repositories;

use App\Models\Categories;
use App\Repositories\Support\AbstractRepository;

class CategoriesRepository extends AbstractRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return Categories::class;
    }

    /**
     * Get categories for dropdown selection
     *
     * @return array
     */
    public function getForSelect()
    {
        return $this->selectArr();
    }

    /**
     * Get parent categories (those without parent_id)
     *
     * @return mixed
     */
    public function getParentCategories()
    {
        return $this->model->whereNull('parent_id')
            ->orWhere('parent_id', 0)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Get active categories
     *
     * @return mixed
     */
    public function getActive()
    {
        return $this->model->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Get categories with their children
     *
     * @return mixed
     */
    public function getWithChildren()
    {
        return $this->model->with('children')
            ->whereNull('parent_id')
            ->orWhere('parent_id', 0)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Get category by slug
     *
     * @param string $slug
     * @return mixed
     */
    public function findBySlug($slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Override queryAll method to include parent relationship
     *
     * @return mixed
     */
    protected function queryAll()
    {
        return $this->model->with('parent');
    }

    /**
     * Toggle category status (active/inactive)
     *
     * @param int $id
     * @return bool
     */
    public function toggleStatus($id)
    {
        $category = $this->find($id);
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';
        return $this->update(['status' => $newStatus], $id) ? true : false;
    }

    /**
     * Validate and create new category
     *
     * @param array $data
     * @return mixed
     */
    public function validateAndCreate(array $data)
    {
        $rules = [
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255', // đã là đường dẫn (controller xử lý upload), không validate bằng rule image để tránh fileinfo
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
        ];

        // Validate and clean the data
        $data = $this->validateAndClean($data, $rules);

        // Set default values
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        if (!isset($data['sort_order'])) {
            $data['sort_order'] = 0;
        }

        // Create the category
        return $this->create($data);
    }

    /**
     * Validate and update category
     *
     * @param array $data
     * @param int $id
     * @return mixed
     */
    public function validateAndUpdate(array $data, $id)
    {
        $category = $this->find($id);

        $rules = [
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255', // đã là đường dẫn (controller xử lý upload), không validate bằng rule image để tránh fileinfo
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
        ];

        // Make sure category can't be its own parent
        if (isset($data['parent_id']) && $data['parent_id'] == $id) {
            unset($data['parent_id']);
        }

        // Validate and clean the data
        $data = $this->validateAndClean($data, $rules);

        // Update the category
        return $this->update($data, $id);
    }
    // Trong CategoriesRepository
    public function getChildCategories($parentId)
    {
        return $this->model->where('parent_id', $parentId)
            ->where('status', 'active')
            ->get();
    }
}
