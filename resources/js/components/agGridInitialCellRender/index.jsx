import React, {Component} from 'react'
import CheckIcon from "@material-ui/core/SvgIcon/SvgIcon";

export default class agGridInitialCellRender extends Component {
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
                <div className="circle-name">
                    {this.props.value.substr(0,1)}{this.props.value.substr(this.props.value.indexOf(' ')+1,1)}
                </div>
            </div>
        )
    }
}

