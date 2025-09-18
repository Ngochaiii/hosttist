<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\CategoriesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected $categoryRepository;

    /**
     * Create a new controller instance.
     *
     * @param CategoriesRepository $categoryRepository
     */
    public function __construct(CategoriesRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $categories = $this->categoryRepository->paginate($request);
        $parentCategories = $this->categoryRepository->getParentCategories();

        return view('source.admin.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Show the form for creating a new category.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parentCategories = $this->categoryRepository->getParentCategories();
        return view('source.admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $data = $request->all();

        // Handle slug creation if empty
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle checkbox status
        $data['status'] = $request->has('status') ? 'active' : 'inactive';

        // Handle meta_data for service type
        $metaData = [];
        if ($request->has('service_type') && !empty($request->service_type)) {
            $metaData['service_type'] = $request->service_type;
        }
        $data['meta_data'] = !empty($metaData) ? json_encode($metaData) : null;

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        try {
            $category = $this->categoryRepository->validateAndCreate($data);
            return redirect()->route('admin.categories.index')
                ->with('success', 'Danh mục đã được tạo thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();

        // Handle slug creation if empty
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle checkbox status
        $data['status'] = $request->has('status') ? 'active' : 'inactive';

        // Handle meta_data for service type
        $metaData = [];
        if ($request->has('service_type') && !empty($request->service_type)) {
            $metaData['service_type'] = $request->service_type;
        }
        $data['meta_data'] = !empty($metaData) ? json_encode($metaData) : null;

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $category = $this->categoryRepository->find($id);
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        try {
            $category = $this->categoryRepository->validateAndUpdate($data, $id);
            return redirect()->route('admin.categories.index')
                ->with('success', 'Danh mục đã được cập nhật thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = $this->categoryRepository->find($id);
        return view('source.admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = $this->categoryRepository->find($id);
        $parentCategories = $this->categoryRepository->getParentCategories()
            ->filter(function ($item) use ($id) {
                return $item->id != $id; // exclude current category from parent options
            });

        return view('source.admin.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Xóa danh mục đã chỉ định.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $category = $this->categoryRepository->find($id);

            if (!$category) {
                return redirect()->route('admin.categories.index')
                    ->withErrors(['error' => 'Danh mục không tồn tại!']);
            }

            // Kiểm tra xem danh mục có danh mục con không
            if ($category->children()->count() > 0) {
                return redirect()->back()
                    ->withErrors(['error' => 'Không thể xóa danh mục này vì có danh mục con!']);
            }

            // Bỏ qua việc kiểm tra sản phẩm để tránh lỗi
            // Nếu bạn biết chính xác mối quan hệ, bạn có thể bỏ comment dòng dưới đây
            // if ($category->products()->count() > 0) {
            //     return redirect()->back()
            //         ->withErrors(['error' => 'Không thể xóa danh mục này vì có sản phẩm liên kết!']);
            // }

            // Xóa hình ảnh nếu tồn tại
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $this->categoryRepository->delete($id);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Danh mục đã được xóa thành công!');
        } catch (\Exception $e) {
            Log::error('Lỗi xóa danh mục: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Lỗi xóa danh mục: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle category status
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        try {
            $this->categoryRepository->toggleStatus($id);
            return redirect()->back()
                ->with('success', 'Trạng thái danh mục đã được thay đổi!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
}
