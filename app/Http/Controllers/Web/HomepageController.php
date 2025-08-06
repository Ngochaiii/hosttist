<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Products;
use App\Repositories\ProductsRepository;
use App\Repositories\CategoriesRepository;

class HomepageController extends Controller
{
    protected $productsRepository;
    protected $categoriesRepository;

    /**
     * Khởi tạo controller
     *
     * @param ProductsRepository $productsRepository
     * @param CategoriesRepository $categoriesRepository
     */
    public function __construct(
        ProductsRepository $productsRepository,
        CategoriesRepository $categoriesRepository
    ) {
        $this->productsRepository = $productsRepository;
        $this->categoriesRepository = $categoriesRepository;
    }

    /**
     * Hiển thị trang chủ
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Lấy các danh mục dịch vụ cho phần "Our Services"
        $services = Categories::where('status', 'active')
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        // Lấy danh sách tất cả các danh mục
        $categories = Categories::where('status', 'active')->get();
        $featuredProducts = Products::forSale()
            ->where('is_featured', true)
            ->with('category')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();
        // Phần hosting solutions giữ nguyên
        $hostingSolutions = collect();
        foreach ($categories as $category) {
            $product = $this->productsRepository->getByCategory($category->id, 1)
                ->where('product_status', 'active')
                ->sortByDesc('is_featured')
                ->sortBy('sort_order')
                ->first();

            if ($product) {
                $product->categoryObject = $category;
                $hostingSolutions->push($product);
            }

            if ($hostingSolutions->count() >= 6) {
                break;
            }
        }

        $compacts = [
            'services' => $services,
            'hostingSolutions' => $hostingSolutions,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts
        ];

        return view('source.web.homepage.homepage', $compacts);
    }


    /**
     * Hiển thị chi tiết sản phẩm/dịch vụ
     *
     * @param string $slug
     * @return \Illuminate\Http\Response
     */
    public function detail($slug)
    {
        // Tìm sản phẩm theo slug
        $product = $this->productsRepository->findBySlug($slug);

        if (!$product || $product->product_status != 'active') {
            return abort(404);
        }

        // Lấy các sản phẩm liên quan cùng danh mục
        $relatedProducts = collect([]);
        if ($product->category_id) {
            $relatedProducts = $this->productsRepository->getByCategory($product->category_id, 4)
                ->where('id', '!=', $product->id)
                ->where('product_status', 'active');
        }

        // Lấy các biến thể của sản phẩm nếu có
        $variants = $this->productsRepository->getVariants($product->id)
            ->where('product_status', 'active');

        $compacts = [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'variants' => $variants
        ];

        return view('source.web.homepage.detail', $compacts);
    }
    public function category($categorySlug)
    {
        // Tìm danh mục theo slug
        $category = $this->categoriesRepository->findBySlug($categorySlug);

        if (!$category || $category->status != 'active') {
            return abort(404);
        }

        // Lấy tất cả sản phẩm trong danh mục
        $products = $this->productsRepository->getByCategory($category->id)
            ->where('product_status', 'active')
            ->sortBy('sort_order');

        $compacts = [
            'category' => $category,
            'products' => $products
        ];

        return view('source.web.category.category', $compacts);
    }
}
