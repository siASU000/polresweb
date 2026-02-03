// db.js
const mysql = require("mysql2");

const db = mysql.createConnection({
  host: "localhost", // ganti dengan host database Anda
  user: "root",      // ganti dengan user MySQL Anda
  password: "",      // ganti dengan password MySQL Anda
  database: "polresta_padang", // ganti dengan nama database Anda
});

db.connect((err) => {
  if (err) {
    console.log("Gagal terhubung ke database:", err);
    return;
  }
  console.log("Berhasil terhubung ke database");
});

module.exports = db;
