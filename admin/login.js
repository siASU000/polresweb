document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();  // Mencegah form submit secara default

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    // Kirim data login ke server menggunakan POST
    fetch("login.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.json())  // Mengambil respons dalam format JSON
    .then(data => {
        if (data.status === "success") {
            alert("Login berhasil!");
            window.location.href = "dashboard.php";  // Redirect ke halaman dashboard jika login berhasil
        } else {
            alert(data.message || "Login gagal");
        }
    })
    .catch(error => {
        console.error("Error during login:", error);
        alert("Terjadi kesalahan saat login.");
    });
});
