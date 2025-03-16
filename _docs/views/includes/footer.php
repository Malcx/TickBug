<!--
views/includes/footer.php
The main footer template that's included at the bottom of each page
-->
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> TickBug. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Mobile navigation toggle
        document.getElementById('navToggle').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('show');
        });
    </script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>



<?php

// Output project-specific theme if we're on a project page
if (isset($project) && isset($project['theme_color'])) {
    $colors = generateThemeColors($project['theme_color']);
    
    // Parse the dark color to get RGB components for rgba usage
    $hex = ltrim($colors['dark'], '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    echo '<style type="text/css">
        :root {
            --dark: ' . $colors['dark'] . ';
            --darker: ' . $colors['darker'] . ';
            --light: ' . $colors['light'] . ';
            --dark-rgb: ' . $r . ', ' . $g . ', ' . $b . ';
        }
    </style>';
}
?>




</body>
</html>