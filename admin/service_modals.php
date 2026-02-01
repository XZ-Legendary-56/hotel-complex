<?php
// service_modals.php - модальные окна для услуг
foreach ($services as $service):
?>
<!-- Модальное окно редактирования -->
<div class="modal fade" id="editServiceModal<?php echo $service['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать услугу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                    <!-- ... остальной код модального окна редактирования ... -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="update_service" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно удаления -->
<div class="modal fade" id="deleteServiceModal<?php echo $service['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Удалить услугу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                    <p>Вы уверены, что хотите удалить услугу "<?php echo htmlspecialchars($service['name']); ?>"?</p>
                    <p class="text-danger"><small>Это действие нельзя отменить.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="delete_service" class="btn btn-danger">Удалить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>