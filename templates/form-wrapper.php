<!-- form-template.php -->
<?php if (!defined('ABSPATH')) exit; ?>
<?php $min_price = (float) get_option('citycourier_minimum_price'); ?>

<script>
  var citycourierSettings = {
    km_price: <?php echo (float) get_option('citycourier_km_price', 0); ?>,
    minimum_price: <?php echo (float) get_option('citycourier_minimum_price', 0); ?>
  };
  window.citycourierSettings = citycourierSettings; // Global erişim için
</script>
<form id="citycourier-form" class="citycourier-form" method="post" action="">
	  <?php wp_nonce_field('citycourier_form_submit', 'cc_nonce'); ?>

  <!-- Teslimat Tipi -->
  <div class="delivery-type-options city-column1">
    <label>
  <input type="radio" name="delivery_type" value="now" checked>
  <div class="option-card"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path fill="#969493" fill-rule="evenodd" d="M11.727 1.668h.183c.965 0 1.727.75 1.758 1.691v.25c0 .032-.031.059-.059.059h-3.554c-.028 0-.055-.027-.055-.059v-.25c0-.941.766-1.691 1.727-1.691m.226 3h-.078C6.996 4.641 3.027 8.582 3 13.461c-.027 4.875 3.918 8.844 8.793 8.871 4.875.027 8.848-3.914 8.875-8.793.05-4.848-3.84-8.82-8.715-8.871m1.379 8.035c.05.84-.57 1.574-1.398 1.629a1.517 1.517 0 0 1-1.602-1.418v-.156l1.5-6.09Zm0 0" clip-rule="evenodd"></path></svg><h3> Motor</h3><br>
<p>
	We will assign a nearby 2-Wheeler to pick up and deliver as soon as possible.
	  </p>
<h5>from ₺ <?php echo $min_price; ?></h5></div>
	</label>
    <label>
      <input type="radio" name="delivery_type" value="truck">
      <div class="option-card"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path fill="#969493" fill-rule="evenodd" d="M14.129 17.977H9.105a2.837 2.837 0 0 1-2.832 2.714 2.837 2.837 0 0 1-2.832-2.714H1.363a.899.899 0 0 1-.886-1.036L1.625 5.434a.9.9 0 0 1 .89-.766H13.23c.497 0 .899.402.899.898Zm-9.637-.145c0 1.012.82 1.836 1.832 1.836s1.832-.824 1.832-1.836a1.832 1.832 0 1 0-3.664 0m10.406.14h-.007V5.98h5.214c.852 0 1.586.586 1.774 1.415l1.633 9.484a.895.895 0 0 1-.875 1.094h-2.075a2.838 2.838 0 0 1-2.832 2.718 2.838 2.838 0 0 1-2.832-2.718m1.32-10.663h3.454c.316 0 .473 0 .601.054.114.051.211.133.282.235.082.117.11.273.164.586l.578 3.164c.039.218.058.324.027.41a.321.321 0 0 1-.144.176c-.078.043-.188.043-.407.043H16.75c-.187 0-.277 0-.352-.036a.323.323 0 0 1-.144-.144c-.035-.07-.035-.164-.035-.352Zm-.394 10.523c0 1.012.82 1.836 1.832 1.836s1.836-.824 1.836-1.836S18.668 16 17.656 16s-1.832.82-1.832 1.832m0 0" clip-rule="evenodd"></path></svg><h3> Araba</h3><br>
<p>
	Book a truck to deliver shipments of up to 1,000 kg. Toll fee included.
	  </p><h5>from ₺ 200</h5></div>
    </label>

  </div>

  <!-- Ağırlık Seçimi -->
  <div class="weight-options city-column1">
    <label><input type="radio" name="weight" value="1kg" checked>1 kg dan az</label>
    <label><input type="radio" name="weight" value="5kg">5 kg dan az</label>
    <label><input type="radio" name="weight" value="10kg">10 kg dan az</label>
    <label><input type="radio" name="weight" value="15kg">15 kg dan az</label>
  </div>

  <!-- Adresler -->
<div class="form-wrapper">
  <!-- Sol Taraftaki Göstergeler -->
  <div class="step-indicator">
    <div class="circle">1</div>
    <div class="line"></div>
    <div class="circle">2</div>
  </div>

  <!-- Sağ Taraftaki Formlar -->
  <div class="form-sections">
    <div class="address-section">
      <h3>Gönderici Bilgileri</h3>
      <input type="text" id="address_from" name="address_from" placeholder="Street name & Locality Name" required>
      <button type="button" class="toggle-map-btn" data-target="map_from">Haritayı Aç</button>
      <div id="map_from" class="map-area" style="display: none; width:100%;height:200px;margin-bottom:10px;"></div>

      <div class="phone_container">
        <div class="phone_prefix">+90</div>
        <input class="phone_input" autocomplete="off" placeholder="5xxxxxxxxx" type="tel" name="pickup_phone" required maxlength="10">
      </div>
    <input type="email"  name="user_email"        placeholder="E-Posta Adresiniz" required>

      <textarea name="pickup_details" placeholder="Daire, kat, bina vb."></textarea>
      <input type="text" id="sender_name" name="sender_name" placeholder="Gönderici Adı" required>
    </div>

    <div class="address-section-2">
      <h3>Alıcı Bilgileri</h3>
      <input type="text" id="address_to" name="address_to" placeholder="Street name & Locality Name" required>
      <button type="button" class="toggle-map-btn" data-target="map_to">Haritayı Aç</button>
      <div id="map_to" class="map-area" style="display: none; width:100%;height:200px;margin-bottom:10px;"></div>

      <input type="tel" name="delivery_phone" placeholder="10 haneli telefon" required>
      <textarea name="delivery_details" placeholder="Daire, kat, bina vb."></textarea>
      <input type="text" id="user_name" name="user_name" placeholder="Alıcı Adı" required>
    </div>
  </div>
</div>




  <!-- Paket içeriği -->
  <div class="package-section city-column1">
    <input type="text" name="package_content" placeholder="Ne gönderiyorsunuz? (Ör: Evrak, Çiçek, Pasta)" required>
    <div class="quick-tags">
      <span>Evrak</span><span>Kutu</span><span>Yemek</span><span>Çiçek</span><span>Pasta</span><span>Poşet</span>
    </div>
  </div>

  <!-- Ödeme Tipi -->
  <div class="payment-type city-column1">
    <label><input type="radio" name="payment" value="cash" checked><img src="https://test.gksoft.com.tr/wp-content/uploads/2025/06/cash-icon_f3b7d04d3b258d28af55.svg">Cash</label>
    <label><input type="radio" name="payment" value="card">Card</label>
  </div>

<div class="form-footer-block city-column1">
  <div class="order-total-block">
    <div class="dv-order-total-price__label">Total:&nbsp;from&nbsp;</div>
    <div class="dv-order-total-price__value"><span>₺ <?php echo $min_price; ?></span></div>
  <div class="order-total-tooltip">
      <span class="tooltip-icon" tabindex="0">?</span>
      <div class="tooltip-content">
        <div>
          Fiyat, seçtiğiniz hizmete göre değişebilir.
        </div>
      </div>
    </div>
  </div>
	<input type="hidden" name="form_type" value="delivery">
<input type="hidden" name="cc_nonce" value="<?php echo wp_create_nonce('citycourier_form_submit'); ?>">

  <div class="form-submit-block">
    <button type="submit" class="form-submit-btn">Siparişi Gönder</button>
  </div>
  <div class="form-submit-note">
    <p>
      ‘Siparişi Gönder’ butonuna basarak <a href="/terms" target="_blank">Şartlar ve Koşullar</a> ile <a href="/privacy" target="_blank">Gizlilik Politikası</a>nı kabul etmiş olursunuz.
    </p>
  </div>
  <p><button type="button" class="action-link" onclick="window.scrollTo({top:0,behavior:'smooth'});">Başa dön</button></p>
</div>



</form>

<div id="cc-response" style="margin-top:1em;color:#900;"></div>

<script>
jQuery(function($){
  var form = $('#citycourier-form');
  var btn  = form.find('button[type=submit]');
  var responseBox = $('#cc-response');

  // Önce eski submit handler varsa kaldır
  form.off('submit').on('submit', function(e){
    e.preventDefault();

    // Eğer buton zaten disable ise ikinci defa çalışmasın
    if ( btn.prop('disabled') ) {
      return;
    }

    // Disable & yükleniyor mesajı
    btn.prop('disabled', true).text('Gönderiliyor…');
    responseBox.text('');

    $.ajax({
      url: '',
      method: 'POST',
      data: form.serialize(),
      dataType: 'json',
    })
    .done(function(res){
      if ( res.success ) {
        var redirectURL = (res.data && res.data.redirect_url) || res.redirect_url;
        if ( redirectURL ) {
          window.location.href = redirectURL;
        } else {
          responseBox.text('Yönlendirme adresi bulunamadı.');
        }
      } else {
        // Hata mesajını ekrana bas
        var err = (res.data) || res.message || 'Bir hata oluştu, lütfen tekrar deneyin.';
        responseBox.text(err);
      }
    })
    .fail(function(xhr){
      var msg = 'Sunucu hatası, lütfen tekrar deneyin.';
      if ( xhr.responseJSON ) {
        msg = xhr.responseJSON.data || xhr.responseJSON.message || msg;
      }
      responseBox.text(msg);
    })
    .always(function(){
      // Butonu tekrar aç
      btn.prop('disabled', false).text('Siparişi Gönder');
    });
  });
});


</script>

<style>
.form-footer-block {margin-top:36px; background:#fafcff; border-radius:14px; box-shadow:0 1px 6px #ececec; padding:28px 18px;}
.order-total-block {display:flex; gap:8px; font-size:32px; margin-bottom:18px;}
.order-total-label {color:#333;}
.order-total-value span {font-weight:600; color:#1564ff; font-size:24px;}
.order-total-tooltip {position:relative; display:inline-block;}
.tooltip-icon {background:#1564ff; color:#fff; width:22px; height:22px; display:inline-block; border-radius:50%; text-align:center; font-size:15px; cursor:pointer; margin-left:6px; font-family:Arial; line-height:22px;}
.tooltip-content {
  display:none; position:absolute; left:50%; transform:translateX(-50%); top:32px; background:#222; color:#fff;
  border-radius:6px; padding:12px 22px; z-index:12; white-space:nowrap; font-size:15px; min-width:120px; box-shadow:0 2px 12px #3331;
}
.order-total-tooltip:focus-within .tooltip-content,
.order-total-tooltip:hover .tooltip-content {display:block;}
.form-submit-block {margin:18px 0 10px 0;}
.form-submit-btn {background:#1564ff; color:#fff; border:none; border-radius:8px; font-size:18px; padding:12px 48px; font-weight:600; cursor:pointer; transition:.2s;}
.form-submit-btn:hover {background:#003ea6;}
.form-submit-note {font-size:13px; color:#666;}
.action-link {background:none; color:#1564ff; border:none; text-decoration:underline; cursor:pointer; font-size:15px;}
</style>
<style>
.citycourier-form { max-width:940px; font-family:sans-serif; margin: 20px auto;}
.delivery-type-options { display:flex; gap:12px; margin-bottom:16px; }
.delivery-type-options label { flex:1; cursor:pointer; }
.option-card { background:#f9f9f9; border-radius:12px; padding:12px; border:1px solid #e0e0e0; text-align:center; transition:.2s; }
.delivery-type-options input[type=radio]:checked + .option-card { border:2px solid #3c7cff; background:#eef4ff; }
.weight-options, .payment-type { display:flex; gap:12px; margin:12px 0; }
.weight-options label, .payment-type label { flex:1; background:#f6f6f6; border-radius:10px; padding:8px 12px; text-align:center; border:1px solid #e0e0e0; cursor:pointer; }
.address-section, .address-section-2, .city-column1{
  background: #fff;
  box-shadow: 0 2px 8px rgba(49, 50, 56, .1);
  padding: 26px 22px 18px 22px;
  margin-bottom: 24px;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
  transition: box-shadow 0.2s;
  border: 1.5px solid #eaf2ff;
}

.address-section:hover, .address-section-2:hover {
  box-shadow: 0 4px 18px 0 #b6cbf533;
}

.address-section h3, .address-section-2 h3 {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 22px;
  color: #2358d5;
  letter-spacing: -0.5px;
}

.address-section input,
.address-section-2 input,
.address-section textarea,
.address-section-2 textarea {
  display: block;
  width: 100%;
  background: #f8fbff;
  border: 1px solid #e0e7ef;
  border-radius: 9px;
  font-size: 15px;
  padding: 11px 14px;
  margin-bottom: 16px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.address-section input:focus,
.address-section-2 input:focus,
.address-section textarea:focus {
  border-color: #1564ff;
  box-shadow: 0 0 0 1.5px #1564ff55;
}

.address-section textarea,
.address-section-2 textarea {
  min-height: 42px;
  max-height: 120px;
  resize: vertical;
}

#map_from, #map_to {
  border-radius: 9px;
  overflow: hidden;
  border: 1px solid #e0e7ef;
  margin-bottom: 16px;
}

@media (max-width: 700px) {
  .address-section, .address-section-2 {
    max-width: 98vw;
    padding: 16px 6vw 12px 6vw;
  }
  .address-section h3, .address-section-2 h3 {
    font-size: 16px;
    margin-bottom: 12px;
  }
}
	.toggle-map-btn {
  background: #eef4ff;
  color: #2358d5;
  border: 1.5px solid #dbe7ff;
  border-radius: 7px;
  padding: 7px 14px;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 10px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.toggle-map-btn:hover {
  background: #2358d5;
  color: #fff;
}

.package-section input { width:100%; padding:8px; border:1px solid #d4d4d4; border-radius:8px; }
.quick-tags { margin:6px 0 0 0; }
.quick-tags span { display:inline-block; background:#eef4ff; color:#3c7cff; padding:3px 10px; border-radius:8px; margin-right:6px; font-size:13px; }
.button-primary { background:#1564ff; color:#fff; border:none; border-radius:10px; font-size:18px; padding:10px 36px; cursor:pointer; }
.button-primary:hover { background:#003ea6; }
</style>


<style>
.form-wrapper {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  position: relative;
}

.step-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-right: 20px;
  margin-top: 20px;
}

.step-indicator .circle {
  width: 26px;
  height: 26px;
  background-color: #fff;
  border: 2px solid #007bff;
  border-radius: 50%;
  text-align: center;
  line-height: 22px;
  font-weight: bold;
  font-size: 14px;
  color: #007bff;
  z-index: 2;
}

.step-indicator .line {
  width: 2px;
  height: 400px;
  background-color: #007bff;
  margin: 6px 0;
}

.form-sections {
  display: flex;
  flex-direction: column;
  gap: 50px; /* Gönderici ve Alıcı arası boşluk */
}

.address-section,
.address-section-2 {
  padding: 20px;
  border: 1px solid #ddd;
  background: #fff;
  width: 700px;
    box-shadow: 0 2px 8px rgba(49, 50, 56, .1);}

.phone_container {
  display: flex;
  gap: 6px;
  align-items: center;
  margin-top: 8px;
}

.phone_prefix {
  font-weight: bold;
}

.phone_input {
  flex: 1;
  padding: 8px;
  border-radius: 4px;
  border: 1px solid #ccc;
}

</style>

<script>
// Basit step geçişleri ve özet doldurma
jQuery(function($){
  // Özet doldurma
  $('#citycourier-form').on('click','.next-step[data-next="4"]',function(){
    $('#sum_sender').text($('[name="sender_name"]').val());
    $('#sum_user').text($('[name="user_name"]').val());
    $('#sum_from').text($('#address_from_result').text());
    $('#sum_to').text($('#address_to_result').text());
    $('#sum_type').text($('[name="package_type"]').val());
    $('#sum_weight').text($('[name="package_weight"]').val());
    $('#sum_unit').text($('[name="weight_unit"]').val());
    $('#sum_slot').text($('[name="delivery_slot"]').val());
  });
});
	
// Harita toggle (isteğe bağlı, Pro sürümde kullanılabilir)
document.querySelectorAll('.toggle-map-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var map = document.getElementById(this.dataset.target);
    map.style.display = (map.style.display==='block'?'none':'block');
    this.textContent = (map.style.display==='block'?'Haritayı Kapat':'Haritayı Aç');
  });
});

</script>
