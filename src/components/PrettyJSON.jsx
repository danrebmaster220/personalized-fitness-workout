import React, { useState } from "react";
import "../styles/PrettyJSON.css";

export default function PrettyJSON({ label, data }) {
  const [open, setOpen] = useState(true);

  // Highlight JSON using simple regex
  const highlight = (json) => {
    if (!json) return "";

    return json
      .replace(/("(?:\\.|[^"\\])*")(\s*:)?/g, (match, p1, p2) => {
        if (p2) return `<span class="json-key">${p1}</span><span class="json-colon">${p2}</span>`;
        return `<span class="json-string">${p1}</span>`;
      })
      .replace(/\b(true|false)\b/g, `<span class="json-boolean">$1</span>`)
      .replace(/\b(null)\b/g, `<span class="json-null">$1</span>`)
      .replace(/\b(\d+)\b/g, `<span class="json-number">$1</span>`);
  };

  const formatted = (() => {
    try {
      return JSON.stringify(
        typeof data === "string" ? JSON.parse(data) : data,
        null,
        2
      );
    } catch {
      return typeof data === "string" ? data : JSON.stringify(data, null, 2);
    }
  })();

  const copyToClipboard = () => {
    navigator.clipboard.writeText(formatted);
  };

  return (
    <div className="json-box">
      <div className="json-header">
        <strong>{label}</strong>

        <div className="json-actions">
          <button className="json-btn small" onClick={() => setOpen(!open)}>
            {open ? "Hide" : "Show"}
          </button>
          <button className="json-btn small" onClick={copyToClipboard}>
            Copy
          </button>
        </div>
      </div>

      {open && (
        <pre
          className="json-pre pretty"
          dangerouslySetInnerHTML={{ __html: highlight(formatted) }}
        ></pre>
      )}
    </div>
  );
}
