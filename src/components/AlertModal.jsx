import React, { useEffect } from 'react';
import { createPortal } from 'react-dom';
import '../styles/AlertModal.css';

export default function AlertModal({ isOpen, title, message, buttonText = 'OK', onClose }) {
  useEffect(() => {
    const prev = document.body.style.overflow;
    if (isOpen) document.body.style.overflow = 'hidden';
    return () => { document.body.style.overflow = prev; };
  }, [isOpen]);

  if (!isOpen) return null;

  const node = (
    <div className="alert-modal-overlay" role="dialog" aria-modal="true">
      <div className="alert-modal-box">
        {title && <h3 className="alert-title">{title}</h3>}
        <div className="alert-body">{message}</div>
        <div className="alert-actions">
          <button className="btn btn-primary" onClick={onClose}>{buttonText}</button>
        </div>
      </div>
    </div>
  );

  return createPortal(node, document.body);
}
