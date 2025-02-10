<?php

$volumes = json_decode(file_get_contents($config['volumes_path']), true);
$groups = parse_etc_group();

?>

<?php foreach ($volumes as $volume_name => $volume) {
          $volume_path = $volume['path'];
          $user_permissions = [];

          
          foreach ($volume['permissions'] as $permission) {
              if (in_array($_SESSION['username'], $groups[$permission["group"]]["users"]) && count($permission["permissions"]) > count($user_permissions)) {
                  $user_permissions = $permission["permissions"];
              }
          }

          if (empty($user_permissions)) continue;
?>
<details>
    <summary class="rounded hover:cursor-pointer hover:bg-[rgba(0,0,255,0.1)] p-2 pl-4" data-volume="<?= $volume_name ?>" data-permission="<?= join(',', $user_permissions) ?>">&nbsp;<?= $volume_name ?></summary>
    <!-- <div class="pl-8">
        <?php
        $files = scandir($volume_path);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..' || is_file(join('/', [$volume_path, $file]))) continue;
            // echo "<div class='p-2'>$file</div>";
        }
        ?>
    </div> -->
</details>
<?php } ?>