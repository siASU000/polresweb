const express = require('express');
const mysql = require('mysql');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const app = express();

app.use(express.json());

const db = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'polresta_padang',
});

app.post('/login', (req, res) => {
  const { username, password } = req.body;

  db.query('SELECT * FROM users WHERE username = ?', [username], (err, result) => {
    if (err) {
      return res.status(500).json({ message: 'Database error', error: err });
    }

    if (result.length === 0) {
      return res.status(400).json({ message: 'Username or password incorrect' });
    }

    const user = result[0];

    // Membandingkan password
    bcrypt.compare(password, user.password, (err, isMatch) => {
      if (err) return res.status(500).json({ message: 'Error comparing password', error: err });
      if (!isMatch) return res.status(400).json({ message: 'Invalid credentials' });

      // Membuat JWT token jika login berhasil
      const token = jwt.sign({ userId: user.id, username: user.username }, 'your-secret-key', { expiresIn: '1h' });

      res.status(200).json({ message: 'Login successful', token });
    });
  });
});

// Menjalankan server pada port 3000
app.listen(3000, () => {
  console.log('Server running on port 3000');
});
