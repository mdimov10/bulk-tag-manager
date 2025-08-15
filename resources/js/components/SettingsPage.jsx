import { Card, Layout, Page, Button, Frame, Toast, Text, Link, Select } from "@shopify/polaris";
import { useState, useEffect } from "react";
import useAxios from "../hooks/useAxios.js";

const SettingsPage = () => {
    const axios = useAxios();
    const [loading, setLoading] = useState(false);
    const [toastActive, setToastActive] = useState(false);
    const [locales, setLocales] = useState([]);
    const [selectedLocale, setSelectedLocale] = useState("");
    const [message, setMessage] = useState("");

    useEffect(() => {
        const fetchLocales = async () => {
            try {
                const res = await axios.get("/api/locales");
                const localeOptions = res.data.map(l => ({
                    label: l.name + (l.installed ? " (Installed)" : ""),
                    value: l.locale,
                    disabled: l.installed
                }));
                setLocales(localeOptions);
                const defaultLocale = res.data.find(l => l.locale.startsWith("bg"));
                if (defaultLocale) setSelectedLocale(defaultLocale.locale);
            } catch (e) {
                console.error("Failed to fetch locales", e);
            }
        };

        fetchLocales();
    }, []);

    const handleInstallSnippet = async () => {
        if (!selectedLocale) {
            alert("Please select a locale.");
            return;
        }

        const supportedLocales = 'bg,ro,hr,hu' || {};
        if (!supportedLocales.includes(selectedLocale)) {
            console.log(supportedLocales, selectedLocale);
            alert("Selected locale is not supported for dual pricing.");
            return;
        }

        setLoading(true);
        try {
            const res = await axios.post("/api/install-price-snippet", { locale: selectedLocale });

            if (res.data?.message) {
                setToastActive(true);
                setMessage(res.data.message);
                setLocales(prev => prev.map(l => l.value === selectedLocale ? { ...l, disabled: true, label: l.label + " (Installed)" } : l));
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
                setMessage("Snippet removed successfully.");
                setLocales(prev => prev.map(l => l.value === selectedLocale ? { ...l, disabled: false, label: l.label.replace(" (Installed)", "") } : l));
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

    return (
        <Page title="Euro Adoption">
            <Layout>
                <Layout.Section>
                    <Card title="What this app does" sectioned>
                        <Text as="h2" variant="headingMd">
                            What this app does
                        </Text>
                        <Text as="p" variant="bodyMd">
                            Bulgaria is adopting the Euro, and during the transition period, stores are required by law to show product prices in both BGN and EUR. You can read more about this regulation <Link url="https://www.fiscal-requirements.com/news/2804" external>here</Link>.
                        </Text> <br />
                        <Text as="p" variant="bodyMd">
                            This app helps stores stay compliant by injecting dual-price code into their active Shopify theme — including product cards, product pages, and the cart.
                        </Text> <br />

                        <Text as="p" variant="bodyMd">
                            While this app was initially created to help Bulgarian merchants, it is designed to support other countries in the future as they transition to the Euro.
                        </Text> <br />

                        <Text as="p" variant="bodyMd">
                            Please note: The core features are pending approval for theme access.
                        </Text>
                    </Card>
                </Layout.Section>

                <Layout.Section>
                    <Card sectioned>
                         <Text as="h2" variant="headingMd">
                            Dual Pricing Setup
                        </Text>

                        <Text as="p" variant="bodyMd">
                            Choose your locale and install the dual pricing snippet into your current theme.
                        </Text>

                        <Select
                            label="Select locale"
                            options={locales}
                            onChange={setSelectedLocale}
                            value={selectedLocale}
                        />

                        <div style={{ marginTop: "1rem", display: "flex", gap: "1rem" }}>
                            <Button
                                onClick={handleInstallSnippet}
                                primary
                                loading={loading}
                                disabled={!selectedLocale || locales.find(l => l.value === selectedLocale)?.disabled}
                            >
                                Install Dual Pricing
                            </Button>
                            <Button
                                onClick={handleRemoveSnippet}
                                destructive
                                loading={loading}
                                disabled={!selectedLocale || !locales.find(l => l.value === selectedLocale)?.disabled}
                            >
                                Remove
                            </Button>
                        </div>
                    </Card>
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
