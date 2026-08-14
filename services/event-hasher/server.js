'use strict';

const crypto = require('crypto');
const express = require('express');

const app = express();
const PORT = Number(process.env.PORT || 8090);
const TOKEN = process.env.EVENT_HASHER_TOKEN || 'uwezo-internal-hasher-change-me';
const HOST = process.env.HOST || '0.0.0.0';

app.use(express.json({ limit: '256kb' }));

function unauthorized(res) {
  return res.status(401).json({ error: 'unauthorized' });
}

function requireToken(req, res, next) {
  const header = req.get('authorization') || '';
  const match = header.match(/^Bearer\s+(.+)$/i);
  const token = match ? match[1].trim() : (req.get('x-internal-token') || '');
  if (!token || token !== TOKEN) {
    return unauthorized(res);
  }
  return next();
}

function canonicalize(value) {
  if (value === null || typeof value !== 'object') {
    return value;
  }
  if (Array.isArray(value)) {
    return value.map(canonicalize);
  }
  const out = {};
  for (const key of Object.keys(value).sort()) {
    out[key] = canonicalize(value[key]);
  }
  return out;
}

function sha256Hex(input) {
  return crypto.createHash('sha256').update(input).digest('hex');
}

app.get('/health', (_req, res) => {
  res.json({ ok: true, service: 'uwezo-event-hasher' });
});

app.post('/v1/hash', requireToken, (req, res) => {
  const body = req.body || {};
  let eventHash = typeof body.event_hash === 'string' ? body.event_hash.trim().toLowerCase() : '';

  if (eventHash) {
    if (!/^[a-f0-9]{64}$/.test(eventHash)) {
      return res.status(400).json({ error: 'event_hash must be a 64-char hex SHA-256 digest' });
    }
  } else if (body.payload !== undefined) {
    const canonical = JSON.stringify(canonicalize(body.payload));
    eventHash = sha256Hex(canonical);
  } else {
    return res.status(400).json({ error: 'Provide payload or event_hash' });
  }

  const receiptId = crypto.randomUUID();
  const createdAt = new Date().toISOString();

  return res.status(201).json({
    receipt_id: receiptId,
    event_hash: eventHash,
    algorithm: 'sha256',
    created_at: createdAt,
  });
});

app.listen(PORT, HOST, () => {
  // eslint-disable-next-line no-console
  console.log(`uwezo-event-hasher listening on ${HOST}:${PORT}`);
});
