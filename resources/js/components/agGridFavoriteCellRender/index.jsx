import React, {Component} from 'react'
import CheckIcon from '@material-ui/icons/Done';
import './../../i18n';
import { NamespacesConsumer } from 'react-i18next';

export default class agGridFavoritCellRender extends Component {
    constructor(props) {
        super(props)

        this.invokeParentMethod = this.invokeParentMethod.bind(this)
    }

    invokeParentMethod() {
        this.props.context.componentParent.methodFromParent(`Row: ${this.props.node.rowIndex}, Col: ${this.props.colDef.headerName}`)
    }

    render() {
        const { classes } = this.props
        var language = this.props.data.prescriber.language === 'LANG_FR' ? 'fr':'en';
        let value = this.props.value === "JMAKER_STATE_ACTIVE"? <CheckIcon style={{marginLeft:'10%',color:'#39cfb4'}}/> : "";
        return (

            <div style={{marginTop:'12%',color:'#616770',display:'grid',lineHeight:'32px'}}>
                <NamespacesConsumer initialLanguage={language}>{(t) =>  t('activated')}</NamespacesConsumer>
                {value}
            </div>
        )
    }
}

