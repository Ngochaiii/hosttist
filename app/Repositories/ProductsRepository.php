<?php

namespace App\Repositories;

use App\Models\Products;
use App\Repositories\Support\AbstractRepository;

class ProductsRepository extends AbstractRepository
{
    /**
     * Chỉ định tên lớp Model
     *
     * @return string
     */
    function model()
    {
        return Products::class;
    }

    /**
     * Lấy dữ liệu sản phẩm cho dropdown
     *
     * @return array
     */
    public function getForSelect()
    {
        return $this->selectArr();
    }

    /**
     * Lấy các sản phẩm dạng biến thể
     *
     * @param int $parent_id
     * @return mixed
     */
    public function getVariants($parent_id)
    {
        return $this->model->where('parent_product_id', $parent_id)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Lấy các sản phẩm được hiển thị (active)
     *
     * @return mixed
     */
    public function getActive()
    {
        return $this->model->where('product_status', 'active')
            ->whereNull('customer_id')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Lấy sản phẩm dựa trên slug
     *
     * @param string $slug
     * @return mixed
     */
    public function findBySlug($slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Lấy sản phẩm nổi bật
     *
     * @param int $limit
     * @return mixed
     */
    public function getFeatured($limit = 10)
    {
        return $this->model->where('product_status', 'active')
            ->where('is_featured', 1)
            ->whereNull('customer_id')
            ->orderBy('sort_order', 'ASC')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy sản phẩm theo loại
     *
     * @param string $type
     * @param int $limit
     * @return mixed
     */
    public function getByType($type, $limit = null)
    {
        $query = $this->model->where('type', $type)
            ->where('product_status', 'active')
            ->whereNull('customer_id')
            ->orderBy('sort_order', 'ASC');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Lấy sản phẩm theo danh mục
     *
     * @param int $category_id
     * @param int $limit
     * @return mixed
     */
    public function getByCategory($category_id, $limit = null)
    {
        $query = $this->model->where('category_id', $category_id)
            ->where('product_status', 'active')
            ->whereNull('customer_id')
            ->orderBy('sort_order', 'ASC');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Ghi đè phương thức queryAll để include các relationships
     *
     * @return mixed
     */
    protected function queryAll()
    {
        return $this->model->with(['category', 'parentProduct']);
    }

    /**
     * Chuyển đổi trạng thái sản phẩm
     *
     * @param int $id
     * @return bool
     */
    public function toggleStatus($id)
    {
        $product = $this->find($id);
        $newStatus = $product->product_status === 'active' ? 'inactive' : 'active';
        return $this->update(['product_status' => $newStatus], $id) ? true : false;
    }

    /**
     * Xác thực và tạo sản phẩm mới
     *
     * @param array $data
     * @return mixed
     */
    public function validateAndCreate(array $data)
    {
        $rules = [
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products,slug',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable', // Bỏ |string để chấp nhận HTML từ CKEditor
            'short_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255', // đã là đường dẫn (controller xử lý upload), không validate bằng rule image để tránh fileinfo
            'type' => 'required|in:product,service,ssl,domain,hosting',
            'product_status' => 'nullable|in:active,inactive,draft',
            'stock' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'meta_data' => 'nullable', // Bỏ quy tắc xác thực JSON
            'options' => 'nullable', // Bỏ quy tắc xác thực JSON
        ];

        // Xác thực và làm sạch dữ liệu
        $data = $this->validateAndClean($data, $rules);

        // Đặt giá trị mặc định
        if (!isset($data['product_status'])) {
            $data['product_status'] = 'active';
        }

        if (!isset($data['sort_order'])) {
            $data['sort_order'] = 0;
        }

        if (!isset($data['is_featured'])) {
            $data['is_featured'] = 0;
        }

        if (!isset($data['stock'])) {
            $data['stock'] = -1; // Không giới hạn
        }

        // Xử lý dữ liệu JSON - vẫn chuyển đổi array thành JSON nếu là array
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            $data['meta_data'] = json_encode($data['meta_data']);
        }

        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }

        // Tạo sản phẩm
        return $this->create($data);
    }

    /**
     * Xác thực và cập nhật sản phẩm
     *
     * @param array $data
     * @param int $id
     * @return mixed
     */
    public function validateAndUpdate(array $data, $id)
    {
        $product = $this->find($id);

        $rules = [
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products,slug,' . $id,
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255', // đã là đường dẫn (controller xử lý upload), không validate bằng rule image để tránh fileinfo
            'type' => 'required|in:product,service,ssl,domain,hosting',
            'product_status' => 'nullable|in:active,inactive,draft',
            'stock' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        // Xác thực và làm sạch dữ liệu
        $data = $this->validateAndClean($data, $rules);

        // Xử lý dữ liệu JSON
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            $data['meta_data'] = json_encode($data['meta_data']);
        }

        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }

        // Cập nhật sản phẩm
        return $this->update($data, $id);
    }
    public function getPossibleParents()
    {
        return $this->model->whereNull('parent_product_id')
            ->whereNull('customer_id')
            ->orderBy('name', 'ASC')
            ->get();
    }
    public function getPossibleParentsExcept($excludeId)
    {
        return $this->model->whereNull('parent_product_id')
            ->where('id', '!=', $excludeId)
            ->whereNull('customer_id')
            ->orderBy('name', 'ASC')
            ->get();
    }
}
