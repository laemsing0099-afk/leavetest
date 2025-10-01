<footer class="mt-5 py-3 bg-light">
    <div class="container">
        <p class="text-center text-muted mb-0">ระบบจัดการลางาน &copy; <?php echo date('Y'); ?></p>
    </div>
</footer>

<!-- Bootstrap 5 JS (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ฟังก์ชันยืนยันการกระทำสำคัญ (หากใช้ SweetAlert2 แนะนำเปลี่ยนเป็นแบบ popup สวยๆ ด้านล่าง)
function confirmAction(message) {
    return confirm(message || 'คุณแน่ใจหรือไม่?');
}

/* 
// ตัวอย่าง confirm แบบ SweetAlert2
function confirmAction(message) {
    return Swal.fire({
        icon: 'question',
        title: 'ยืนยันการทำรายการ',
        text: message || 'คุณแน่ใจหรือไม่?',
        showCancelButton: true,
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก'
    }).then(result => result.isConfirmed);
}
*/
</script>
</body>
</html>
