<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-edit"></i>
        تعديل بيانات المكتب
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <h3 class="mb-4">بيانات المكتب</h3>
    <div class="row">
        <div class="form-group col-md-6">
            <x-form.input type="text" id="name" name="name" label="اسم المكتب" placeholder="أدخل اسم المكتب" required />
        </div>
        <div class="form-group col-md-6">
            <x-form.input type="text" id="location" name="location" label="الموقع" placeholder="أدخل موقع المكتب" />
        </div>
        <div class="form-group col-md-12" hidden>
            <label for="is_active" class="form-label">الحالة</label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">مفعل</label>
            </div>
        </div>
        <div class="form-group col-md-12">
            <x-form.textarea id="notes" name="notes" label="ملاحظات" rows="3" placeholder="أدخل أي ملاحظات" />
        </div>
    </div>
</div>

<div class="modal-footer">
    <div class="gap-2 d-flex justify-content-end" id="btns_form">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
            <i class="fas fa-times"></i>
            إغلاق
        </button>
        <button type="button" id="update" class="btn btn-primary">
            <i class="fas fa-save"></i>
            حفظ التعديلات
        </button>
    </div>
</div>
