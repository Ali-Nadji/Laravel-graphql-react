import React, {Component} from 'react'
import CheckIcon from "@material-ui/core/SvgIcon/SvgIcon";

export default class agGridNameCellRender extends Component {
    constructor(props) {
        super(props)

        this.invokeParentMethod = this.invokeParentMethod.bind(this)
    }

    invokeParentMethod() {
        this.props.context.componentParent.methodFromParent(`Row: ${this.props.node.rowIndex}, Col: ${this.props.colDef.headerName}`)
    }

    render() {
        const { classes } = this.props
        return (
            <div>
                <p style={{fontSize:'12px',color:'#616770',marginBottom:'-3%'}}>{this.props.value.substr(this.props.value.indexOf('-')+1)}</p>
                <p style={{fontSize:'16px',marginTop:'-6%'}}>{this.props.value.substr(0,this.props.value.indexOf('-'))}</p>
            </div>
        )
    }
}

