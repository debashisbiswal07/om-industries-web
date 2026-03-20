import { Component, OnInit } from '@angular/core';
import { ICellRendererAngularComp } from 'ag-grid-angular';
import { ICellRendererParams } from 'ag-grid-community';

@Component({
  selector: 'app-pin-renderer',
  templateUrl: './pin-renderer.component.html',
  styleUrls: ['./pin-renderer.component.scss']
})
export class PinRendererComponent implements ICellRendererAngularComp {

  public cellValue!: any;
  public pinDataList: any;

  // gets called once before the renderer is used
  agInit(params: ICellRendererParams): void { 
    this.cellValue = this.getValueToDisplay(params);
    this.pinDataList = params.value;
    this.pinDataList?.sort((a: any, b: any) => a.pinConfigOrder < b.pinConfigOrder ? -1 : 1);
  }

  // gets called whenever the user gets the cell to refresh
  refresh(params: ICellRendererParams) {
    // set value into cell again
    this.cellValue = this.getValueToDisplay(params);
    return true;
  }

  getValueToDisplay(params: ICellRendererParams) {
    return params.valueFormatted ? params.valueFormatted : params.value;
  }
}
