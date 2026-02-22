<x-front-layout>
    @push('styles')
        <style>
            .import-card {
                border: 1px solid #edf2f7;
                border-radius: 14px;
                box-shadow: 0 1px 8px rgba(15, 23, 42, 0.05);
            }
            .import-upload-zone {
                border: 2px dashed #cbd5e1;
                border-radius: 10px;
                background: #f8fafc;
                padding: 1.5rem;
            }
        </style>
    @endpush

    <div class="m-4">
        <div class="card import-card overflow-hidden">
            <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="mb-0">استيراد المساعدات من Excel</h5>
                <p class="mb-0 mt-1 small text-muted">ارفع ملف الإكسل وفق قالب الاستيراد ثم اضغط رفع</p>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dashboard.import.excel') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="import-upload-zone mb-4">
                        <label for="import-file" class="form-label fw-medium">اختر الملف</label>
                        <input type="file" name="file" id="import-file" class="form-control" accept=".xlsx,.xls">
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">رفع</button>
                        <a href="{{ asset('filesExcel/templateAidDistribution.xlsx') }}" download="قالب المساعدات.xlsx" class="btn btn-outline-secondary">تحميل نموذج الاستيراد</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-front-layout>
