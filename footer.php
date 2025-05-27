<?php
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section about">
                <h3 class="logo-text">k8servers</h3>
                <p>
                    Tu plataforma ideal para alojar y gestionar tus proyectos web
                    con total libertad y control.
                </p>
                <div class="contact">
                    <span><i class="fas fa-phone"></i> &nbsp; +34 644825621</span>
                    <span><i class="fas fa-envelope"></i> &nbsp; soporte@k8servers.es</span>
                </div>
            </div>

            <div class="footer-section links">
                <h3>Enlaces Rápidos</h3>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="index.php#features">Características</a></li>
                    <li><a href="index.php#pricing">Precios</a></li>
                    <li><a href="sobre_nosotros.php">Sobre Nosotros</a></li>
                    <li><a href="contacto.php">Contacto</a></li>
                </ul>
            </div>

            <div class="footer-section links">
                <h3>Legal</h3>
                <ul>
                    <li><a href="terminos.php">Términos y Condiciones</a></li>
                    <li><a href="privacidad.php">Política de Privacidad</a></li>
                    <li><a href="cookies.php">Política de Cookies</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> k8servers.es | Todos los derechos reservados.
        </div>
    </div>
</footer>

<div id="cookieConsentBanner" style="display:none; position: fixed; bottom: 0; left: 0; width: 100%; background-color: #333; color: white; padding: 15px; text-align: center; z-index: 10000;">
    <p style="margin: 0 0 10px 0;">Este sitio web utiliza cookies para mejorar su experiencia. Al continuar navegando, acepta nuestro uso de cookies.</p>
    <div>
        <button id="acceptAllCookies" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px;">Aceptar Todas</button>
        <button id="configureCookies" style="padding: 8px 15px; background-color: #555; color: white; border: none; cursor: pointer; margin-right: 10px;">Configurar</button>
        <button id="rejectAllCookies" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; cursor: pointer;">Rechazar Todas</button>
    </div>
</div>

<div id="cookieSettingsModal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: var(--card-bg); color: var(--text-color); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--box-shadow-current); z-index: 10001; width: 90%; max-width: 500px;">
    <h3 style="margin-top:0;">Configuración de Cookies</h3>
    <p>Selecciona las cookies que deseas aceptar:</p>
    <form id="cookiePreferencesForm">
        <div>
            <input type="checkbox" id="cookies_necesarias" name="necesarias" checked disabled>
            <label for="cookies_necesarias">Necesarias (siempre activas)</label>
        </div>
        <div>
            <input type="checkbox" id="cookies_analiticas" name="analiticas" checked>
            <label for="cookies_analiticas">Analíticas</label>
            <small style="display:block; color: var(--text-color-muted);">Nos ayudan a entender cómo usas el sitio.</small>
        </div>
        <div>
            <input type="checkbox" id="cookies_marketing" name="marketing" checked>
            <label for="cookies_marketing">Marketing</label>
            <small style="display:block; color: var(--text-color-muted);">Para mostrarte anuncios relevantes.</small>
        </div>
        <div>
            <input type="checkbox" id="cookies_funcionales" name="funcionales" checked>
            <label for="cookies_funcionales">Funcionales</label>
            <small style="display:block; color: var(--text-color-muted);">Recuerdan tus preferencias (ej. idioma).</small>
        </div>
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" id="saveCookiePreferences" class="btn btn-primary">Guardar Preferencias</button>
            <button type="button" id="closeCookieModal" class="btn btn-outline" style="margin-left:10px;">Cerrar</button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const banner = document.getElementById("cookieConsentBanner");
  const modal = document.getElementById("cookieSettingsModal");
  const acceptAllBtn = document.getElementById("acceptAllCookies");
  const configureBtn = document.getElementById("configureCookies");
  const rejectAllBtn = document.getElementById("rejectAllCookies");
  const savePreferencesBtn = document.getElementById("saveCookiePreferences");
  const closeModalBtn = document.getElementById("closeCookieModal");
  const preferencesForm = document.getElementById("cookiePreferencesForm");

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  async function saveConsent(preferences, source = "banner_cookies") {
    const formData = new FormData();
    formData.append("action", "guardar_consentimiento");
    formData.append("preferencias", JSON.stringify(preferences));
    formData.append("fuente", source);

    try {
      const response = await fetch("gestion_consentimiento.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.status === "success") {
        if (banner) banner.style.display = "none";
        if (modal) modal.style.display = "none";
        console.log("Consentimiento guardado:", result.message);
      } else {
        console.error("Error al guardar consentimiento:", result.message);
        alert("Error al guardar tus preferencias de cookies.");
      }
    } catch (error) {
      console.error("Fetch error:", error);
      alert("Error de conexión al guardar preferencias.");
    }
  }

  if (banner && !getCookie("cookie_consent_given")) {
    banner.style.display = "block";
  }

  if (acceptAllBtn) {
    acceptAllBtn.addEventListener("click", function () {
      saveConsent(
        {
          aceptar_todas: true,
          necesarias: true,
          analiticas: true,
          marketing: true,
          funcionales: true,
        },
        "banner_aceptar_todas",
      );
    });
  }

  if (rejectAllBtn) {
    rejectAllBtn.addEventListener("click", function () {
      saveConsent(
        {
          necesarias: true,
          analiticas: false,
          marketing: false,
          funcionales: false,
        },
        "banner_rechazar_todas",
      );
    });
  }

  if (configureBtn && modal) {
    configureBtn.addEventListener("click", function () {
      if (banner) banner.style.display = "none";
      modal.style.display = "block";
    });
  }

  if (closeModalBtn && modal) {
    closeModalBtn.addEventListener("click", function () {
      modal.style.display = "none";
      if (banner && !getCookie("cookie_consent_given")) {
        banner.style.display = "block";
      }
    });
  }

  if (savePreferencesBtn && preferencesForm && modal) {
    savePreferencesBtn.addEventListener("click", function () {
      const prefs = {
        necesarias: true,
      };
      prefs.analiticas = preferencesForm.elements["analiticas"].checked;
      prefs.marketing = preferencesForm.elements["marketing"].checked;
      prefs.funcionales = preferencesForm.elements["funcionales"].checked;
      saveConsent(prefs, "modal_configuracion");
    });
  }
});
</script>

<?php
?>
