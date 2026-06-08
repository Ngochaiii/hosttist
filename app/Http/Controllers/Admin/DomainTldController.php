<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainTld;
use Illuminate\Http\Request;

/**
 * Quản lý danh mục đuôi tên miền (TLD) + giá gốc/markup.
 * Giá bán & lãi tự tính từ accessor trên model — admin chỉ nhập cost + markup.
 */
class DomainTldController extends Controller
{
    public function index(Request $request)
    {
        $tlds    = DomainTld::orderBy('sort_order')->orderBy('tld')->get();
        $editing = $request->filled('edit') ? DomainTld::find($request->edit) : null;

        return view('source.admin.domain_tlds.index', compact('tlds', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DomainTld::create($data);

        return redirect()->route('admin.domain-tlds.index')
            ->with('success', 'Đã thêm đuôi tên miền .' . $data['tld']);
    }

    public function update(Request $request, $id)
    {
        $tld  = DomainTld::findOrFail($id);
        $data = $this->validateData($request, $tld->id);

        $tld->update($data);

        return redirect()->route('admin.domain-tlds.index')
            ->with('success', 'Đã cập nhật đuôi .' . $tld->tld);
    }

    public function toggleStatus($id)
    {
        $tld = DomainTld::findOrFail($id);
        $tld->update(['is_active' => !$tld->is_active]);

        return back()->with('success', 'Đã đổi trạng thái .' . $tld->tld);
    }

    public function destroy($id)
    {
        $tld = DomainTld::findOrFail($id);

        if ($tld->domains()->exists()) {
            return back()->withErrors(['error' => 'Không thể xoá: đuôi .' . $tld->tld . ' đang có tên miền gắn vào.']);
        }

        $tld->delete();

        return back()->with('success', 'Đã xoá đuôi .' . $tld->tld);
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $unique = 'unique:domain_tlds,tld' . ($ignoreId ? ',' . $ignoreId : '');

        $validated = $request->validate([
            'tld'           => ['required', 'string', 'max:30', 'regex:/^[a-z0-9]+(\.[a-z0-9]+)*$/', $unique],
            'is_vn'         => ['nullable'],
            'register_cost' => ['required', 'numeric', 'min:0'],
            'renew_cost'    => ['required', 'numeric', 'min:0'],
            'transfer_cost' => ['nullable', 'numeric', 'min:0'],
            'markup_type'   => ['required', 'in:amount,percent'],
            'markup_value'  => ['required', 'numeric', 'min:0'],
            'round_to'      => ['nullable', 'integer', 'min:0'],
            'min_years'     => ['required', 'integer', 'min:1', 'max:10'],
            'max_years'     => ['required', 'integer', 'min:1', 'max:10'],
            'sort_order'    => ['nullable', 'integer'],
        ], [], [
            'tld' => 'đuôi tên miền',
        ]);

        $validated['tld']        = strtolower(ltrim($validated['tld'], '.'));
        $validated['is_vn']      = $request->boolean('is_vn');
        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
