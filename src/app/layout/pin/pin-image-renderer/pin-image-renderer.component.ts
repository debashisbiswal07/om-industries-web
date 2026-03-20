import { Component, OnInit } from '@angular/core';
import { ICellRendererAngularComp } from 'ag-grid-angular';
import { ICellRendererParams } from 'ag-grid-community';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-pin-image-renderer',
  templateUrl: './pin-image-renderer.component.html',
  styleUrls: ['./pin-image-renderer.component.scss']
})
export class PinImageRendererComponent implements ICellRendererAngularComp {
  public imgBaseUrl: string = environment.imgPath;
  public imgSrc!: string;

  // gets called once before the renderer is used
  agInit(params: ICellRendererParams): void { 
    //this.cellValue = this.getValueToDisplay(params);
    this.imgSrc = this.imgBaseUrl + params.value;
    //console.log(this.imgSrc);
  }

  // gets called whenever the user gets the cell to refresh
  refresh(params: ICellRendererParams) {
    // set value into cell again
    this.imgSrc = this.getValueToDisplay(params);
    return true;
  }

  getValueToDisplay(params: ICellRendererParams) {
    return params.valueFormatted ? params.valueFormatted : params.value;
  }
}
