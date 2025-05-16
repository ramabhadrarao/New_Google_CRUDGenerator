          </div> </div> <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item"><a href="#" class="link-secondary">Documentation</a></li>
                  <li class="list-inline-item"><a href="#" class="link-secondary">License</a></li>
                </ul>
              </div>
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">
                    Copyright &copy; <?php echo date("Y"); ?>
                    <a href="." class="link-secondary"><?php echo htmlspecialchars($appName ?? 'My App'); ?></a>.
                    All rights reserved.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
      </div> </div> <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../js/custom_script.js"></script> <?php
    // Flash messages display using Tabler Alerts
    $messages = get_flash_messages();
    if (!empty($messages)): ?>
    <div style="position: fixed; top: 1rem; right: 1rem; z-index: 1050;">
        <?php foreach ($messages as $type => $msgs): ?>
            <?php foreach ($msgs as $msg): ?>
                <div class="alert alert-<?php echo ($type == 'success' ? 'success' : 'danger'); ?> alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div>
                            <?php if ($type == 'success'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M5 12l5 5l10 -10"></path></svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 9v2m0 4v.01"></path><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path></svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="alert-title"><?php echo ucfirst($type); ?>!</h4>
                            <div class="text-muted"><?php echo htmlspecialchars($msg); ?></div>
                        </div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <script>
    // Auto-dismiss alerts after 5 seconds
    window.setTimeout(function() {
        $(".alert-dismissible").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove();
        });
    }, 5000);
    </script>
    <?php endif; ?>
  </body>
</html>