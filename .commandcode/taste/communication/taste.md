# Communication

- Communicates in Bahasa Indonesia and expects the agent to respond in Bahasa Indonesia, keeping technical terms (e.g., panel, resource, URL, menu) in English. Confidence: 0.9
- Asking "kenapa" (why) about a bug signals a desire for the underlying mechanism explained (e.g., why hardcoding dates would still not fix it), not just what was changed — root-cause reasoning belongs in the answer. Confidence: 0.6
- When pointing at a specific file/line, the user often asks the agent to "kritisi" (critique) rather than just fix — expecting a critical assessment that surfaces remaining issues/contradictions (e.g., a seeder still hardcoding `transaction_at => now` despite a 90-day spread fix), not an obedient change. Confidence: 0.5
