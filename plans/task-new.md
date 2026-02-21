# إضافة حقل "القيمة التقديرية" في جدول أنواع المساعدات

المشروع Laravel (Aid Registry).

المطلوب:
إضافة حقل جديد باسم "estimated_value" (القيمة التقديرية) داخل جدول أنواع المساعدات (aid_types أو الجدول المستخدم حالياً)، بحيث يكون:
- رقم فقط
- يقبل كسور عشرية (لدعم مبالغ مالية)
- اختياري nullable (حسب سياسة النظام)
- يظهر في الفورم (create + edit)
- يُعرض في شاشة العرض إن وجدت

---

## 1) Migration

أنشئ migration جديد يضيف الحقل:

- الاسم: `estimated_value`
- النوع: decimal(10,2)
- nullable
- بعد عمود مناسب (مثلاً بعد name أو description)

مثال منطقي:
$table->decimal('estimated_value', 10, 2)->nullable()->after('name');

لا تقم بتعديل migration قديم — استخدم migration جديد فقط.

---

## 2) Model (AidType.php)

- أضف الحقل إلى `$fillable`
- أضف cast:
  'estimated_value' => 'decimal:2'

---

## 3) Validation (Controller)

في store و update:

- 'estimated_value' => 'nullable|numeric|min:0'

تأكد:
- منع القيم السالبة
- السماح بالقيمة 0 إن لزم

---

## 4) تعديل الفورم (Blade)

في:
resources/views/dashboard/aid_types/_form.blade.php
أو الفورم المستخدم فعلياً

أضف حقل:

- type="number"
- step="0.01"
- min="0"
- name="estimated_value"

مع:
- old('estimated_value', $aidType->estimated_value ?? '')

---

## 5) العرض (Index / Show)

إذا توجد شاشة عرض لأنواع المساعدات:
- أضف عمود في الجدول لعرض القيمة
- نسّقها كرقم ثابت منزلتين
- لا تستخدم تنسيق عملة ثابتة إذا النظام متعدد العملات

---

## 6) اختبار بعد التنفيذ

- إنشاء نوع مساعدة مع قيمة
- إنشاء نوع بدون قيمة
- تعديل القيمة
- إدخال رقم عشري
- إدخال رقم سالب (يجب رفضه)
- إدخال نص (يجب رفضه)

---

## ملاحظات مهمة

- لا تربط القيمة التقديرية مباشرة بحسابات التوزيع حالياً.
- هذا الحقل مرجعي فقط (Informational) ما لم يُطلب خلاف ذلك.
- لا تغيّر أي منطق موجود حالياً في aid_distributions.