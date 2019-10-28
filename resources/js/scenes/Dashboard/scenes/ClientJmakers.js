import React from 'react'
import {Query} from "react-apollo";
import gql from 'graphql-tag';
import Gates from "./Gates";
import './../../../i18n';
import { NamespacesConsumer } from 'react-i18next';

const ClientJmakers = () => (

    <Query query={gql`
        {
            jmakerClient{
            uuid
            name
            created_at
            state
            last_page_at
            missions_ct
            meeting_date
            synthesis_shared_at
            default_relance_object_fr
            default_relance_message_fr
            default_relance_object_en
            default_relance_message_en
            language_id
            recall_at
            recall_ct
            prescriber{
                uuid
                email
                language
            }
            }
        }
    `}
    >
        {({loading,error,data}) => {
            if(loading) return <p>Loading....</p>;
            if(error) return <p>{console.log(error)}</p>;
            var language = data.jmakerClient[0].prescriber.language === 'LANG_FR' ? 'fr':'en';
            var locale = data.jmakerClient[0].prescriber.language === 'LANG_FR' ? 'fr-FR':'en-EN';
            return (
                <NamespacesConsumer initialLanguage={language}>
                    {(t) => <Gates jmakers={data.jmakerClient} translate={t} locale={locale} />}
                </NamespacesConsumer>

            );
        }}
    </Query>
);

export default ClientJmakers;