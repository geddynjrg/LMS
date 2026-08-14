# Uwezo Event Hasher

Internal permissioned SHA-256 receipt service for Core/Majuu LMS phase transitions.

- Bind only on the Docker network (do not publish publicly without auth).
- Auth: `Authorization: Bearer <EVENT_HASHER_TOKEN>` or `X-Internal-Token`.
- Clients should hash PII client-side or send non-PII canonical payloads only.
