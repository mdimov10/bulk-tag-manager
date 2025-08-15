import {Banner, Layout, Page} from "@shopify/polaris";

const MissingApiKey = () => {
    return (
        <Page>
            <Layout>
                <Layout.Section>
                    <Banner title="Shopify API key is missing." tone="critical">
                        Shopify API key is missing from the application.
                    </Banner>
                </Layout.Section>
            </Layout>
        </Page>
    )
}

export default MissingApiKey
