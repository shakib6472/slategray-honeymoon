<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}
?>
<div id="serverAlert">
    <div class="notice">
          <span class="icon">ℹ️</span>
          <p class="message">Por favor completa todos los campos</p>
    </div>
    
</div>

 <div class="loader-overlay">
    <div class="loader"></div> 
 </div>
 

  <?php
      if ( is_user_logged_in() ) {
          ?>
          <div class="hm-login-box">
              <p>Ya has iniciado sesión. Ir al panel.</p>

              <a href="<?php echo site_url('/dashboard');?>" class="dashboard-btn">
                  Ir al panel
              </a>
          </div>
          <?php
          return; // 🔥 important (form stop)
      }
 ?>

<!-- Registration Success POPUP -->
 
<div id="successOverlay">

  <div class="success-box">

    <h2>Registro exitoso</h2>

    <p>Tu código de luna de miel:</p>

    <div class="code-box">
      <input type="text" id="userCode" readonly>
      <button id="copyCode">📋</button>
    </div>

    <a href="<?php echo site_url('/dashboard');?>" id="goDashboard" class="dashboard-btn">
      Ir al panel
    </a>

  </div>

</div>

<form id="honey-registrationForm">

  <!-- Husband -->
  <label>Nombre del esposo</label>
  <input type="text" name="husband_first_name" placeholder="Nombre del esposo" required>

  <label>Apellido del esposo</label>
  <input type="text" name="husband_last_name" placeholder="Apellido del esposo" required>

  <!-- Wife -->
  <label>Nombre de la esposa</label>
  <input type="text" name="wife_first_name" placeholder="Nombre de la esposa" required>

  <label>Apellido de la esposa</label>
  <input type="text" name="wife_last_name" placeholder="Apellido de la esposa" required>

  <!-- Email -->
  <label>Dirección de correo electrónico</label>
  <input type="email" name="email" placeholder="Dirección de correo electrónico" required>

  <!-- Password -->
  <div class="password-group">

        <label>Contraseña</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="Contraseña">
            <span class="toggle-pass">👁️</span>
        </div>

        <label>Confirmar Contraseña</label>
        <div class="password-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña">
        </div>

        <small id="passError"></small>

  </div>

  <button type="submit" >Registrar</button>

</form>