import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import 'ag-grid-community/dist/styles/ag-grid.css';
import 'ag-grid-community/dist/styles/ag-theme-balham.css';
import ApolloClient from 'apollo-boost';
import ApolloProvider from "react-apollo/ApolloProvider";
import ClientJmakers from "./scenes/ClientJmakers";

const client = new ApolloClient({
    uri:"https://partner."+env+"/graphql",
    credentials: 'include'
});


class Dashboard extends Component{

    constructor(props) { //<----Method
        super(props);
    }

    render() {
        return (
            <ApolloProvider client={client}>
                <ClientJmakers/>
            </ApolloProvider>
        );
    }
}

export default Dashboard;

if (document.getElementById('dashboard')) {
    ReactDOM.render(<Dashboard />, document.getElementById('dashboard'));
}
