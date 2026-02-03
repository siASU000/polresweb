document.getElementById("createAccountForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const username = document.getElementById("username").value.trim();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirm-password").value;
  const role = document.getElementById("role").value;

  if (password !== confirmPassword) {
    alert("Password dan Confirm Password tidak sama.");
    return;
  }

  const endpoint = "./create-account_api.php";
  const body = new URLSearchParams({ username, password, role }).toString();

  try {
    const response = await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body,
    });

    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      console.error("Response bukan JSON:", text);
      alert("Server tidak mengembalikan JSON. Cek error PHP.");
      return;
    }

    if (!response.ok) {
      alert(data.message || "Server error saat membuat akun.");
      return;
    }

    if (data.status === "success") {
      alert(data.message || "Account created successfully!");
      window.location.href = "login.php";
    } else {
      alert(data.message || "Gagal membuat akun.");
    }
  } catch (error) {
    console.error("Fetch error:", error);
    alert("Gagal konek ke server. Pastikan Apache & MySQL menyala.");
  }
});
