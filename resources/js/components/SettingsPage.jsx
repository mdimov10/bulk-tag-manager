import {Card, Layout, Page, Button, Frame, Toast, Text, Link, Select, Checkbox, Banner, TextField} from "@shopify/polaris";
import { useState, useEffect } from "react";
import useAxios from "../hooks/useAxios.js";

const SettingsPage = () => {
    const axios = useAxios();
    const [loading, setLoading] = useState(false);
    const [toastActive, setToastActive] = useState(false);
    const [locales, setLocales] = useState([]);
    const [selectedLocale, setSelectedLocale] = useState("");
    const [message, setMessage] = useState("");
    const [installSuccess, setInstallSuccess] = useState(false);
    const [installFailed, setInstallFailed] = useState(false);

    const [showPromoStep, setShowPromoStep] = useState(false);
    const [promoCode, setPromoCode] = useState("");
    const [promoError, setPromoError] = useState("");

    // const [checkoutNoticeEnabled, setCheckoutNoticeEnabled] = useState(false);


    useEffect(() => {
        const fetchShopSettings = async () => {
            try {
                const res = await axios.get("/api/shop-settings");
                if (res.data.freemium) {
                    setShowPromoStep(true);
                }
            } catch (err) {
                console.error("Failed to fetch shop settings", err);
            }
        };

        fetchShopSettings();

        const fetchLocales = async () => {
            try {
                const res = await axios.get("/api/locales");
                const localeOptions = res.data.map(l => ({
                    label: l.name + (l.installed ? " (Installed)" : ""),
                    value: l.locale,
                    installed: l.installed // ✅ keep installed flag for logic
                }));
                setLocales(localeOptions);

                // Default to Bulgarian if available
                let defaultLocaleOption = res.data.find(l => l.locale.startsWith("bg"))
                    || res.data.find(l => l.locale.startsWith("en"))
                    || res.data[0]; // fallback to any available locale

                if (defaultLocaleOption) {
                    setSelectedLocale(defaultLocaleOption.locale);
                }
            } catch (e) {
                console.error("Failed to fetch locales", e);
            }
        };

        if(showPromoStep === false) {
            fetchLocales();
        }
        // fetchSettings();
    }, []);

    const handleInstallSnippet = async () => {
        if (!selectedLocale) {
            alert("Please select a locale.");
            return;
        }

        setLoading(true);
        try {
            const res = await axios.post("/api/install-price-snippet", { locale: selectedLocale });
            console.log(res.data);

            if (res.data?.updated_files !== undefined && res.data?.total_files !== undefined) {
                const { updated_files, total_files } = res.data;

                if (updated_files === 0) {
                    setMessage("Dual pricing installation failed. Please contact us and share your theme name so we can add support.");

                    setInstallSuccess(false);
                    setInstallFailed(true);
                } else if (updated_files === total_files) {
                    setMessage("✅ Dual pricing installed successfully.");

                    setInstallSuccess(true);
                    setInstallFailed(false);

                    setLocales(prev => prev.map(l =>
                        l.value === selectedLocale
                            ? {
                                ...l,
                                installed: true,
                                label: l.label.includes("(Installed)") ? l.label : l.label + " (Installed)",
                              }
                            : l
                    ));
                } else {
                    setMessage("⚠️ Dual pricing installed, but some files could not be updated. Please verify your theme or contact us.");

                    setInstallSuccess(true);
                    setInstallFailed(false);

                    setLocales(prev => prev.map(l =>
                        l.value === selectedLocale
                            ? { ...l, disabled: true, label: l.label + " (Installed)" }
                            : l
                    ));
                }

                setToastActive(true);
            } else if (res.data?.message) {
                // Fallback just in case
                setMessage(res.data.message);
                setToastActive(true);
            } else {
                console.error("Unexpected response:", res.data);
            }
        } catch (error) {
            console.error('Error installing snippet:', error?.response?.data || error.message);
            alert(error?.response?.data?.error || "Installation failed.");
        } finally {
            setLoading(false);
        }
    };

    const handleRemoveSnippet = async () => {
        setLoading(true);
        try {
            const res = await axios.post("/api/remove-price-snippet", { locale: selectedLocale });

            if (res.data?.message) {
                setToastActive(true);
                setMessage("Dual pricing removed successfully.");

                setLocales(prev => prev.map(l =>
                    l.value === selectedLocale
                        ? { ...l, label: l.label.replace(" (Installed)", ""), installed: false }
                        : l
                ));
            } else {
                console.error("Unexpected response:", res.data);
            }
        } catch (error) {
            console.error('Error removing snippet:', error?.response?.data || error.message);
            alert(error?.response?.data?.error || "Removal failed.");
        } finally {
            setLoading(false);
        }
    };

    const handleRemoveAllSnippets = async () => {
        setLoading(true);
        try {
            const res = await axios.post("/api/remove-price-snippet"); // No locale

            if (res.data?.message) {
                setToastActive(true);
                setMessage("All app code removed successfully.");
                setLocales(prev => prev.map(l => ({
                    ...l,
                    label: l.label.replace(" (Installed)", ""),
                    installed: false
                })));
            } else {
                console.error("Unexpected response:", res.data);
            }
        } catch (error) {
            console.error('Error removing all snippets:', error?.response?.data || error.message);
            alert(error?.response?.data?.error || "Removal failed.");
        } finally {
            setLoading(false);
        }
    };

    const handleApplyPromo = async () => {
      try {
        await axios.post("/api/activate-custom-plan", { promo_code: promoCode });
        setShowPromoStep(false);

        await axios.get("/");
      } catch (err) {
        setPromoError(err.response?.data?.error || "Something went wrong");
      }
    };

    const handleSkipPromo = async () => {
      try {
        await axios.post("/api/activate-custom-plan", { no_promo: true });
        setShowPromoStep(false);

        await axios.get("/");
      } catch (err) {
          console.log(err);
      }
    };

    if (showPromoStep) {
      return (
        <Page fullWidth>
          <Layout>
            <Layout.Section>
              <Card sectioned>
                <Text as="h2" variant="headingMd">🎁 Enter Promo Code</Text>
                <TextField
                  autoComplete=""
                  label=""
                  value={promoCode}
                  onChange={setPromoCode}
                  error={promoError}
                />
                <div style={{ marginTop: '1rem', display: 'flex', gap: '1rem' }}>
                  <Button variant="primary" onClick={handleApplyPromo}>
                    Apply & Continue
                  </Button>
                  <Button onClick={handleSkipPromo}>I don’t have a code</Button>
                </div>
              </Card>
            </Layout.Section>
          </Layout>
        </Page>
      );
    }

    return (
        <Page fullWidth>
            <Layout>
                {installSuccess && (
                  <Layout.Section>
                    <Banner status="success" tone="success" title="Dual Pricing Installed">
                      <p>Your store now displays prices in both BGN and EUR.</p>
                    </Banner>
                  </Layout.Section>
                )}

                {installFailed && (
                  <Layout.Section>
                    <Banner status="warning" tone="warning" title="Installation Failed">
                      <p>We couldn’t inject the code into your theme. Please contact us and share your theme name so we can add support.</p>
                    </Banner>
                  </Layout.Section>
                )}

                <Layout.Section>
                  <div style={{ textAlign: 'center' }}>
                    <img
                      src="/images/handshake_banner.png"
                      style={{ width: '100%'}}
                      alt=""
                    />
                  </div>
                </Layout.Section>

                <Layout.Section style={{ paddingBottom: "5rem" }}>
                    <Card sectioned>
                         <Text as="h2" variant="headingMd">
                            Dual Pricing Setup
                        </Text>

                        <Text as="p" variant="bodyMd">
                            Choose your locale and install the dual pricing snippet into your current theme.
                        </Text>

                        <Select
                            label="Select locale"
                            options={locales.map(({ installed, ...rest }) => rest)}
                            onChange={setSelectedLocale}
                            value={selectedLocale}
                        />

                        <div style={{ marginTop: "1rem", display: "flex", gap: "1rem" }}>
                           <Button
                                onClick={handleInstallSnippet}
                                primary
                                loading={loading}
                                disabled={
                                    !selectedLocale ||
                                    locales.find(l => l.value === selectedLocale)?.installed
                                }
                            >
                                Install Dual Pricing
                           </Button>
                           <Button
                                onClick={handleRemoveSnippet}
                                destructive
                                loading={loading}
                                disabled={!selectedLocale || !locales.find(l => l.value === selectedLocale)?.installed}
                           >
                               Remove
                           </Button>
                        </div>
                    </Card>
                </Layout.Section>

                <Layout.Section>
                  <Card title="Show EUR Total in Order Confirmation Email" sectioned>
                    <Text as="h2" variant="headingMd">
                      Show EUR in Order Confirmation Email(Including product prices, delivery prices, total price)
                    </Text>

                    <Text as="p" variant="bodyMd">
                      Law requires that the <strong>total amount paid</strong> — including delivery or additional services — is displayed in <strong>both BGN and EUR</strong>.
                    </Text>

                    <Text as="p" variant="bodyMd" tone="subdued" style={{ marginTop: '1rem' }}>
                      Shopify doesn't allow us to modify the checkout page directly, but we can help you comply by replacing your default <strong>order confirmation email</strong> with a ready-made template that includes all required dual pricing.
                    </Text>

                    <div style={{ marginTop: '2rem', padding: '1rem', backgroundColor: '#f6f6f7', borderRadius: '8px' }}>
                      <Text as="h3" variant="headingSm">
                        🔧 Step-by-step Instructions
                      </Text>

                      <ol style={{ paddingLeft: '1.25rem', marginTop: '1rem' }}>
                        <li>
                          Open the complete email template and copy the whole content:
                          <div style={{ marginTop: '0.5rem' }}>
                            <Button
                              size="medium"
                              onClick={() => {
                                window.open("/downloads/order_confirmation_with_eur.liquid", "_blank");
                              }}
                            >
                              Open Template
                            </Button>
                          </div>
                        </li>
                        <li style={{ marginTop: '1rem' }}>
                          Go to <strong>Settings → Notifications → Order Confirmation</strong>
                        </li>
                        <li>
                          Click <strong>Edit code</strong> and <u>replace the entire content</u> with the one you just copied
                        </li>
                        <li>
                          Click <strong>Save</strong> and preview the email — you'll now see EUR prices for products, delivery, and the total
                        </li>
                      </ol>
                    </div>
                  </Card>
                </Layout.Section>

                <Layout.Section>
                    <div style={{ marginTop: "2rem"}}>
                          <Card sectioned>
                            <Text as="h2" variant="headingMd">
                              Uninstalling
                            </Text>
                            <Text as="p" variant="bodyMd" tone="subdued">
                              Planning to uninstall? Click this button to safely remove all app code from your theme.
                            </Text>

                            <div style={{ marginTop: "1rem" }}>
                              <Button
                                  variant="primary"
                                onClick={handleRemoveAllSnippets}
                                destructive
                                size="large"
                                tone="critical"
                                loading={loading}
                              >
                                Remove All App Code
                              </Button>
                            </div>
                          </Card>
                    </div>
                </Layout.Section>
            </Layout>


            {toastActive && (
                <Frame>
                    <Toast
                        content={message || "Operation completed."}
                        onDismiss={() => setToastActive(false)}
                    />
                </Frame>
            )}
        </Page>
    );
};

export default SettingsPage;
