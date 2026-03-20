import { Component, OnInit } from '@angular/core';
import { ColDef } from 'ag-grid-community';
import { AppService }  from '../../app.service';
import { ToastrService } from 'ngx-toastr';
import { Router } from '@angular/router';
import { Pin } from '../../model/pin.model';
import { PinRendererComponent } from './pin-renderer/pin-renderer.component';
import { PinImageRendererComponent } from './pin-image-renderer/pin-image-renderer.component';

@Component({
  selector: 'app-pin',
  templateUrl: './pin.component.html',
  styleUrls: ['./pin.component.scss']
})
export class PinComponent implements OnInit {
  rowHeight: number = 100;
  rowData: any[] = [];
  columnDefs: ColDef[] = [
    { field: "pinDataId", headerName: 'Id', width: 70, minWidth: 70 },
    { field: "companyName", headerName: 'Name', width: 120, minWidth: 120 },
    { field: "pinName", headerName: 'Pin', width: 120, minWidth: 120 },
    { field: "model", headerName: 'Model', width: 150, minWidth: 150 },
    { field: "pinNo", headerName: 'Pin#', width: 80, minWidth: 80 },
    { field: "pinType", headerName: 'Type', width: 100, minWidth: 100 },
    { field: "pinData", headerName: 'Pin Data', width: 250, minWidth: 250, cellRenderer: PinRendererComponent },
    { field: "inductance", headerName: 'Ind', width: 80 },
    { field: "rate", headerName: 'Rate', width: 80 },
    { field: "pinView", headerName: 'View', width: 220, minWidth: 220, cellRenderer: PinImageRendererComponent }
  ];
  public defaultColDef: ColDef = {
    sortable: true,
    filter: true,
    resizable: true,
    wrapText: true, 
    autoHeight: true,
    wrapHeaderText: true,
    autoHeaderHeight: true,
  };
  form: Pin = {
    companyName: '0',
    pinName: '0',
    pinNo: 0,
    pinType: '0',
  };
  companyNameList: any;
  pinNameList: any;
  pinNosList: any;
  pinTypeList: any;
  constructor(private appService :AppService, private toastrService: ToastrService,
    private router: Router) { }

  ngOnInit(): void {
    this.bindDropdown();
    this.getPinData();
  }

  getPinData(){
    this.appService.getPinData(this.form).subscribe(
      response => {
        if (response.result != null) {
          // var colDef: any = []
          // Object.keys(response.result[0]).forEach((key) => {
          //   var objCol: any = null;
          //   if(key == "pinData") {
          //     objCol = { field: key, minWidth: 200, cellRenderer: PinRendererComponent};
          //   }
          //   else {
          //     objCol = { field: key };
          //   }
          //   colDef.push(objCol);
          // });
          // console.log(colDef);
          // this.columnDefs = colDef;
          this.rowData = response.result;
          //console.log(this.rowData);
        }        
      },
      error => {
        console.log(error);
        this.toastrService.error('Message Error!', 'There is network error, please try after sometime.');
      });
  }

  bindDropdown(){
    this.appService.getDropdownData().subscribe(
      response => {
        if (response.result != null) {
          this.companyNameList = response.result.companyName;
          this.pinNameList = response.result.pinName;
          this.pinNosList = response.result.pinNos;
          this.pinTypeList = response.result.pinType;
        }        
      },
      error => {
        console.log(error);
        this.toastrService.error('Message Error!', 'There is network error, please try after sometime.');
      });
  }
}
