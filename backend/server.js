const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const path = require('path');
const mysql = require('mysql2/promise');

const app = express();
const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

// Database connection configuration
const dbConfig = {
  host: 'localhost',
  user: 'root',  // Update with your MySQL username
  password: '',  // Update with your MySQL password
  database: 'medical_system'
};

// Create database connection pool
const pool = mysql.createPool(dbConfig);

// Serve static files from the public directory
app.use(express.static(path.join(__dirname, 'public')));

// Store connected clients and their associated patient IDs
const clients = new Map();

// WebSocket connection handler
wss.on('connection', (ws, req) => {
  console.log('New client connected');
  
  // Extract patient_id from URL parameters
  const url = new URL(req.url, 'ws://localhost');
  const patientId = url.searchParams.get('patient_id');

  if (!patientId) {
    ws.close(1008, 'Missing patient_id');
    return;
  }

  // Store client with their associated patient ID
  clients.set(ws, { patientId });

  ws.on('message', async (message) => {
    try {
      const data = JSON.parse(message.toString());
      
      // Store measurement in database
      const connection = await pool.getConnection();
      try {
        await connection.execute(
          'INSERT INTO mesure (patient_id, temperature, pulsation, date_mesure) VALUES (?, ?, ?, NOW())',
          [patientId, data.temperature, data.heartRate]
        );
      } finally {
        connection.release();
      }

      // Broadcast the message to all connected clients
      clients.forEach((clientData, client) => {
        if (client.readyState === WebSocket.OPEN) {
          client.send(message.toString());
        }
      });
    } catch (error) {
      console.error('Error processing message:', error);
    }
  });

  ws.on('close', () => {
    console.log('Client disconnected');
    clients.delete(ws);
  });
});

// API endpoint to get patient measurements
app.get('/api/measurements/:patientId', async (req, res) => {
  try {
    const { patientId } = req.params;
    const connection = await pool.getConnection();
    
    try {
      const [rows] = await connection.execute(
        'SELECT * FROM mesure WHERE patient_id = ? ORDER BY date_mesure DESC LIMIT 100',
        [patientId]
      );
      res.json(rows);
    } finally {
      connection.release();
    }
  } catch (error) {
    console.error('Error fetching measurements:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Start server
const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
}); 