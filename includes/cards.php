<?php
// Reusable metric card component
// Parameters:
// - $title: string, card title
// - $value: string|int, main value to display
// - $icon: string, FontAwesome icon class (e.g., 'fa-user-graduate')
// - $colorClass: string, optional additional color class for icon or card

function render_metric_card($title, $value, $icon, $colorClass = '') {
    ?>
    <div class="col-12 col-sm-6 col-md-3 col-lg-3">
        <div class="card p-4 rounded shadow-sm text-center" style="background-color: var(--color-card); color: var(--color-text-primary);">
            <div class="card-body">
                <i class="fas <?php echo htmlspecialchars($icon); ?> fa-2x mb-2 <?php echo htmlspecialchars($colorClass); ?>"></i>
                <h5 class="card-title display-6"><?php echo htmlspecialchars($value); ?></h5>
                <p class="card-text text-muted fs-6"><?php echo htmlspecialchars($title); ?></p>
            </div>
        </div>
    </div>
    <?php
}
?>
