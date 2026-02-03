// admin/dashboard.js

const avatarEl = document.getElementById("avatar");
const topUsernameEl = document.getElementById("topUsername");
const roleTextEl = document.getElementById("roleText");
const usernameTextEl = document.getElementById("usernameText");

const fotoInput = document.getElementById("fotoInput");
const btnPilihFoto = document.getElementById("btnPilihFoto");
const btnUploadFoto = document.getElementById("btnUploadFoto");

const form = document.getElementById("profileForm");
const saveStatus = document.getElementById("saveStatus");

function setStatus(msg, isError = false) {
  saveStatus.textContent = msg;
  saveStatus.style.color = isError ? "#b91c1c" : "#0f766e";
  if (msg) setTimeout(() => (saveStatus.textContent = ""), 2500);
}

async function loadProfile() {
  const res = await fetch("profile_get.php", { credentials: "same-origin" });
  const json = await res.json();

  if (!res.ok || json.status !== "success") {
    alert(json.message || "Gagal memuat profil.");
    return;
  }

  const d = json.data || {};

  topUsernameEl.textContent = d.username || "-";
  roleTextEl.textContent = d.role || "-";
  usernameTextEl.textContent = d.username || "-";

  document.getElementById("nama").value = d.nama || "";
  document.getElementById("nrp").value = d.nrp || "";
  document.getElementById("email").value = d.email || "";
  document.getElementById("alamat").value = d.alamat || "";
  document.getElementById("no_hp").value = d.no_hp || "";
  document.getElementById("jabatan").value = d.jabatan || "";
function lockNumericOnly(inputEl) {
  if (!inputEl) return;

  // Blok karakter non-digit saat mengetik
  inputEl.addEventListener("input", () => {
    inputEl.value = inputEl.value.replace(/\D/g, "");
  });

  // Blok paste yang mengandung non-digit
  inputEl.addEventListener("paste", (e) => {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData("text") || "";
    inputEl.value = text.replace(/\D/g, "");
    inputEl.dispatchEvent(new Event("input"));
  });

  // Blok keypress selain digit (opsional tapi membantu UX)
  inputEl.addEventListener("keypress", (e) => {
    const ch = String.fromCharCode(e.which);
    if (!/[0-9]/.test(ch)) e.preventDefault();
  });
}

// panggil setelah loadProfile mengisi value juga aman
lockNumericOnly(document.getElementById("nrp"));
lockNumericOnly(document.getElementById("no_hp"));

  if (d.foto) {
    avatarEl.src = "../" + d.foto;
  }
}

btnPilihFoto.addEventListener("click", () => fotoInput.click());

btnUploadFoto.addEventListener("click", async () => {
  if (!fotoInput.files || !fotoInput.files[0]) {
    setStatus("Pilih foto dulu.", true);
    return;
  }

  const fd = new FormData();
  fd.append("foto", fotoInput.files[0]);

  const res = await fetch("profile_upload.php", {
    method: "POST",
    body: fd,
    credentials: "same-origin"
  });

  const json = await res.json();
  if (!res.ok || json.status !== "success") {
    setStatus(json.message || "Upload gagal.", true);
    return;
  }

  avatarEl.src = "../" + json.foto;
  setStatus("Foto tersimpan.");
});

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const fd = new FormData(form);

  const res = await fetch("profile_update.php", {
    method: "POST",
    body: fd,
    credentials: "same-origin"
  });

  const json = await res.json();
  if (!res.ok || json.status !== "success") {
    setStatus(json.message || "Gagal menyimpan profil.", true);
    return;
  }

  setStatus("Profil tersimpan.");
});

loadProfile().catch((err) => {
  console.error(err);
  alert("Terjadi error saat memuat profil. Cek console.");
});
const passForm = document.getElementById("passwordForm");
const passStatus = document.getElementById("passStatus");

function setPassStatus(msg, isError = false) {
  passStatus.textContent = msg;
  passStatus.style.color = isError ? "#b91c1c" : "#0f766e";
  if (msg) setTimeout(() => (passStatus.textContent = ""), 3000);
}

if (passForm) {
  passForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const oldPw = document.getElementById("old_password").value;
    const newPw = document.getElementById("new_password").value;
    const confPw = document.getElementById("confirm_password").value;

    if (newPw !== confPw) {
      setPassStatus("Konfirmasi password baru tidak sama.", true);
      return;
    }

    const fd = new FormData(passForm);

    const res = await fetch("password_update.php", {
      method: "POST",
      body: fd,
      credentials: "same-origin",
    });

    const json = await res.json();

    if (!res.ok || json.status !== "success") {
      setPassStatus(json.message || "Gagal mengubah password.", true);
      return;
    }

    passForm.reset();
    setPassStatus("Password berhasil diubah.");
  });
}
