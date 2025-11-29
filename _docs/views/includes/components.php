<?php
/**
 * views/includes/components.php
 * Shared reusable HTML components for the TickBug application
 * Reduces duplication across view files
 */

/**
 * Render a breadcrumb navigation
 *
 * @param array $items Array of breadcrumb items [['label' => string, 'url' => string|null], ...]
 *                     Last item should have url as null (active item)
 * @return void
 */
function renderBreadcrumb($items) {
    if (empty($items)) return;

    echo '<nav aria-label="breadcrumb">';
    echo '<ol class="breadcrumb">';

    foreach ($items as $index => $item) {
        $isLast = ($index === count($items) - 1);
        $label = sanitizeOutput($item['label']);

        if ($isLast || empty($item['url'])) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $label . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . sanitizeOutput($item['url']) . '">' . $label . '</a></li>';
        }
    }

    echo '</ol>';
    echo '</nav>';
}

/**
 * Render an alert message
 *
 * @param string $type Alert type (success, danger, warning, info)
 * @param string $message Alert message
 * @param bool $dismissible Whether the alert can be dismissed
 * @return void
 */
function renderAlert($type, $message, $dismissible = false) {
    $class = 'alert alert-' . sanitizeOutput($type);
    if ($dismissible) {
        $class .= ' alert-dismissible';
    }

    echo '<div class="' . $class . '">';
    echo sanitizeOutput($message);
    if ($dismissible) {
        echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    }
    echo '</div>';
}

/**
 * Render a card component
 *
 * @param array $options Card options
 *   - title: Card header title
 *   - headerActions: HTML for header action buttons
 *   - body: Card body content
 *   - footer: Card footer content
 *   - class: Additional CSS classes
 *   - id: Card ID attribute
 *   - dataAttributes: Array of data attributes ['key' => 'value']
 * @return void
 */
function renderCard($options) {
    $class = 'card';
    if (!empty($options['class'])) {
        $class .= ' ' . sanitizeOutput($options['class']);
    }

    $id = !empty($options['id']) ? ' id="' . sanitizeOutput($options['id']) . '"' : '';

    $dataAttrs = '';
    if (!empty($options['dataAttributes'])) {
        foreach ($options['dataAttributes'] as $key => $value) {
            $dataAttrs .= ' data-' . sanitizeOutput($key) . '="' . sanitizeOutput($value) . '"';
        }
    }

    echo '<div class="' . $class . '"' . $id . $dataAttrs . '>';

    // Header
    if (!empty($options['title']) || !empty($options['headerActions'])) {
        echo '<div class="card-header">';
        echo '<div class="row">';
        echo '<div class="col-8"><h5 class="mb-0">' . sanitizeOutput($options['title'] ?? '') . '</h5></div>';
        if (!empty($options['headerActions'])) {
            echo '<div class="col-4 text-right">' . $options['headerActions'] . '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Body
    if (!empty($options['body'])) {
        echo '<div class="card-body">';
        echo $options['body'];
        echo '</div>';
    }

    // Footer
    if (!empty($options['footer'])) {
        echo '<div class="card-footer">';
        echo $options['footer'];
        echo '</div>';
    }

    echo '</div>';
}

/**
 * Render a status badge
 *
 * @param string $status Status name
 * @return string HTML badge
 */
function renderStatusBadge($status) {
    $statusClass = 'badge badge-' . strtolower(str_replace(' ', '-', $status));
    return '<span class="' . $statusClass . '">' . sanitizeOutput($status) . '</span>';
}

/**
 * Render a priority badge
 *
 * @param string $priority Priority name (e.g., "1-Critical", "2-Important")
 * @return string HTML badge
 */
function renderPriorityBadge($priority) {
    $priorityClass = 'badge badge-priority-' . strtolower(str_replace(' ', '-', $priority));
    return '<span class="' . $priorityClass . '">' . sanitizeOutput($priority) . '</span>';
}

/**
 * Render a form group
 *
 * @param array $options Form group options
 *   - id: Input ID
 *   - name: Input name
 *   - label: Label text
 *   - type: Input type (text, email, password, textarea, select)
 *   - value: Current value
 *   - placeholder: Placeholder text
 *   - required: Whether required
 *   - options: For select type, array of options ['value' => 'label']
 *   - rows: For textarea, number of rows
 *   - class: Additional input classes
 * @return void
 */
function renderFormGroup($options) {
    $id = sanitizeOutput($options['id'] ?? $options['name']);
    $name = sanitizeOutput($options['name']);
    $type = $options['type'] ?? 'text';
    $value = sanitizeOutput($options['value'] ?? '');
    $placeholder = sanitizeOutput($options['placeholder'] ?? '');
    $required = !empty($options['required']) ? ' required' : '';
    $class = 'form-control' . (!empty($options['class']) ? ' ' . sanitizeOutput($options['class']) : '');

    echo '<div class="form-group">';

    if (!empty($options['label'])) {
        echo '<label for="' . $id . '">' . sanitizeOutput($options['label']) . '</label>';
    }

    switch ($type) {
        case 'textarea':
            $rows = $options['rows'] ?? 3;
            echo '<textarea id="' . $id . '" name="' . $name . '" class="' . $class . '" rows="' . $rows . '" placeholder="' . $placeholder . '"' . $required . '>' . $value . '</textarea>';
            break;

        case 'select':
            echo '<select id="' . $id . '" name="' . $name . '" class="' . $class . '"' . $required . '>';
            if (!empty($options['options'])) {
                foreach ($options['options'] as $optValue => $optLabel) {
                    $selected = ($value == $optValue) ? ' selected' : '';
                    echo '<option value="' . sanitizeOutput($optValue) . '"' . $selected . '>' . sanitizeOutput($optLabel) . '</option>';
                }
            }
            echo '</select>';
            break;

        default:
            echo '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" class="' . $class . '" value="' . $value . '" placeholder="' . $placeholder . '"' . $required . '>';
    }

    echo '</div>';
}

/**
 * Render a button
 *
 * @param array $options Button options
 *   - type: Button type (button, submit, reset)
 *   - text: Button text
 *   - class: CSS class (btn-primary, btn-secondary, btn-danger)
 *   - id: Button ID
 *   - dataAttributes: Array of data attributes
 * @return string HTML button
 */
function renderButton($options) {
    $type = $options['type'] ?? 'button';
    $text = sanitizeOutput($options['text'] ?? 'Submit');
    $class = 'btn ' . ($options['class'] ?? 'btn-primary');
    $id = !empty($options['id']) ? ' id="' . sanitizeOutput($options['id']) . '"' : '';

    $dataAttrs = '';
    if (!empty($options['dataAttributes'])) {
        foreach ($options['dataAttributes'] as $key => $value) {
            $dataAttrs .= ' data-' . sanitizeOutput($key) . '="' . sanitizeOutput($value) . '"';
        }
    }

    return '<button type="' . $type . '" class="' . $class . '"' . $id . $dataAttrs . '>' . $text . '</button>';
}

/**
 * Render pagination
 *
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL with placeholder for page number
 * @return void
 */
function renderPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return;

    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination">';

    // Previous button
    $prevDisabled = ($currentPage <= 1) ? ' disabled' : '';
    $prevUrl = ($currentPage > 1) ? str_replace('{page}', $currentPage - 1, $baseUrl) : '#';
    echo '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i === $currentPage) ? ' active' : '';
        $url = str_replace('{page}', $i, $baseUrl);
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }

    // Next button
    $nextDisabled = ($currentPage >= $totalPages) ? ' disabled' : '';
    $nextUrl = ($currentPage < $totalPages) ? str_replace('{page}', $currentPage + 1, $baseUrl) : '#';
    echo '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';

    echo '</ul>';
    echo '</nav>';
}

/**
 * Render an empty state message
 *
 * @param string $message Message to display
 * @param string $actionUrl Optional action button URL
 * @param string $actionText Optional action button text
 * @return void
 */
function renderEmptyState($message, $actionUrl = null, $actionText = null) {
    echo '<div class="text-center p-4">';
    echo '<p class="text-muted">' . sanitizeOutput($message) . '</p>';
    if ($actionUrl && $actionText) {
        echo '<a href="' . sanitizeOutput($actionUrl) . '" class="btn btn-primary">' . sanitizeOutput($actionText) . '</a>';
    }
    echo '</div>';
}
