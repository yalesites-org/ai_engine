import styles from "./Layout.module.css";
import { Stack } from "@fluentui/react";
import { useContext, useEffect, useState, useRef } from "react";
import { HistoryButton } from "../../components/common/Button";
import { AppStateContext } from "../../state/AppProvider";
import { CosmosDBStatus } from "../../api";
import Chat from "../../pages/chat/Chat";

import aiLogo from "../../assets/Logo.svg";
import Modal from "../../components/Modal/Modal";

const Layout = () => {
  const appStateContext = useContext(AppStateContext);

  const [isModalOpen, setIsModalOpen] = useState(false);

  const handleOpenModal = () => {
    setIsModalOpen(true);
    document.body.setAttribute("data-modal-active", "true");
    document.body.setAttribute("data-body-frozen", "true");
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    document.body.removeAttribute("data-modal-active");
    document.body.removeAttribute("data-body-frozen");
  };

  const handleHistoryClick = () => {
    appStateContext?.dispatch({ type: "TOGGLE_CHAT_HISTORY" });
  };

  useEffect(() => {}, [appStateContext?.state.isCosmosDBAvailable.status]);

  // Set const for modal footer content
  const LandingFooter = () => {
    return (
      <Stack.Item className={styles.answerDisclaimerContainer}>
        <div className={styles.answerDisclaimer}>
          <span className={styles.answerDisclaimerText}>
            Content is AI-generated and may contain inaccuracies. User
            discretion advised.
          </span>
          <span className={styles.answerDisclaimerSeparator}>|</span>
          <span className={styles.answerDisclaimerText}>
            <a
              href="/#additional-insights"
              title="FAQs"
              onClick={handleCloseModal}
            >
              FAQs
            </a>
          </span>
          <span className={styles.answerDisclaimerSeparator}>|</span>
          <span className={styles.answerDisclaimerText}>
            <a href="/share-your-feedback" title="Share Feedback">
              Share feedback
            </a>
          </span>
        </div>
      </Stack.Item>
    );
  };

  const LandingHeader = () => {
    return (
      <img src={aiLogo} className={styles.modalHeaderTitle} alt="AskYale" />
    );
  };
  /**
   * Close modal on escape key press.
   */
  useEffect(() => {
    const close = (e: { key: string }) => {
      if (e.key === "Escape") {
        handleCloseModal();
      }
    };
    window.addEventListener("keydown", close);
    return () => window.removeEventListener("keydown", close);
  }, []);

  const showHistory = () => {
    appStateContext?.state.isCosmosDBAvailable?.status !==
      CosmosDBStatus.NotConfigured && (
      <HistoryButton
        onClick={handleHistoryClick}
        text={
          appStateContext?.state?.isChatHistoryOpen
            ? "Hide chat history"
            : "Show chat history"
        }
      />
    );
  };
  return (
    <div className={styles.layout}>
      {isModalOpen && (
        <Modal
          show={isModalOpen}
          header={<LandingHeader />}
          footer={<LandingFooter />}
          close={handleCloseModal}
          variant={""}
        >
          <Chat />
        </Modal>
      )}
      <button
        type="button"
        id="launch-chat-modal"
        onClick={handleOpenModal}
        className="visually-hidden"
      >
        Try askYale Now
      </button>
    </div>
  );
};

export default Layout;
