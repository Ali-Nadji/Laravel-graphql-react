import React, {Component} from 'react'
import CheckIcon from "@material-ui/core/SvgIcon/SvgIcon";

export default class agGridActionCellRender extends Component {
    constructor(props) {
        super(props)

        this.invokeParentMethod = this.invokeParentMethod.bind(this)
    }

    invokeParentMethod() {
        this.props.context.componentParent.methodFromParent(`Row: ${this.props.node.rowIndex}, Col: ${this.props.colDef.headerName}`)
    }

    render() {
        const { classes } = this.props
        let value = <button className={'action-btn'}><i className="fa fa-chevron-down" aria-hidden="true" style={{color:'#39cfb4'}}></i></button>
        return (
            <div style={{color:'#616770'}}>{value}</div>
        )
    }
}

