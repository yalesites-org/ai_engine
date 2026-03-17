import React, { useEffect, useRef, useState } from 'react';
import type { ComponentPropsWithoutRef } from 'react';
import styles from './CodeBlock.module.css';

const CodeBlock = ({ children, node, ...rest }: ComponentPropsWithoutRef<'pre'> & { node?: unknown }) => {
  const [copied, setCopied] = useState(false);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, []);

  const handleCopy = async () => {
    const child = Array.isArray(children) ? children[0] : children;
    const childProps = React.isValidElement(child)
      ? (child.props as { children?: unknown })
      : null;
    const codeContent =
      childProps?.children != null ? String(childProps.children) : '';

    if (!navigator.clipboard) return;

    try {
      await navigator.clipboard.writeText(codeContent);
      setCopied(true);

      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
      timerRef.current = setTimeout(() => {
        setCopied(false);
      }, 2000);
    } catch {
      // Clipboard write failed (permission denied or other error) — no-op
    }
  };

  return (
    <div className={styles.codeBlockWrapper}>
      <button
        type="button"
        className={styles.copyButton}
        onClick={handleCopy}
        aria-label={copied ? 'Code copied' : 'Copy code'}
      >
        {copied ? 'Copied!' : 'Copy'}
      </button>
      <pre {...rest}>{children}</pre>
    </div>
  );
};

export default CodeBlock;
