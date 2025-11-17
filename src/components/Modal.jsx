import React from "react";
import "../styles/Modal.css";

export default function Modal({
  title = "Modal Title",
  body = "",
  confirmText = "Confirm",
  cancelText = "Cancel",
  onConfirm,
  onCancel,
  show = false,
  width = "500px"
}) {
  if (!show) return null;

  return (
    <div className="modal-backdrop">
      <div className="modal-container" style={{ maxWidth: width }}>
        <h3 className="modal-title">{title}</h3>

        <div className="modal-body">{body}</div>

        <div className="modal-buttons">
          {cancelText && (
            <button className="modal-btn-cancel" onClick={onCancel}>
              {cancelText}
            </button>
          )}

          <button className="modal-btn-confirm" onClick={onConfirm}>
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}
